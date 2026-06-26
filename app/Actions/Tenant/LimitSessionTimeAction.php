<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Devices;
use App\Models\Tenant\Offer;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\SystemConfig;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LimitSessionTimeAction
{
    /**
     * تحديد إجمالي وقت الجلسة بالدقائق (من بداية الجلسة الأساسية).
     *
     * @throws RuntimeException
     */
    public function execute(int $deviceId, int $totalMinutes): PlaySessionDetail
    {
        return DB::transaction(function () use ($deviceId, $totalMinutes): PlaySessionDetail {
            if ($totalMinutes <= 0) {
                throw new RuntimeException('عدد الدقائق يجب أن يكون أكبر من صفر.');
            }

            if ($totalMinutes > 999) {
                throw new RuntimeException('عدد الدقائق يجب ألا يتجاوز 3 أرقام (999).');
            }

            /** @var Devices $device */
            $device = Devices::query()
                ->whereKey($deviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($device->status !== 'busy') {
                throw new RuntimeException('الجهاز غير مشغول حالياً.');
            }

            /** @var PlaySessions|null $session */
            $session = PlaySessions::query()
                ->where('device_id', $device->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $session) {
                throw new RuntimeException('لا توجد جلسة نشطة لهذا الجهاز.');
            }

            /** @var PlaySessionDetail|null $currentDetail */
            $currentDetail = PlaySessionDetail::query()
                ->where('play_session_id', $session->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $currentDetail) {
                throw new RuntimeException('لا توجد بيانات جلسة نشطة لهذا الجهاز.');
            }

            /** @var PlaySessionDetail|null $sessionFirstDetail */
            $sessionFirstDetail = PlaySessionDetail::query()
                ->where('play_session_id', $session->id)
                ->orderBy('id', 'asc')
                ->first();

            if (! $sessionFirstDetail?->start_time) {
                throw new RuntimeException('بداية الجلسة غير متاحة.');
            }

            $baseStartTime = $sessionFirstDetail->start_time;
            $currentSegmentStart = $currentDetail->start_time;

            if (! $currentSegmentStart) {
                throw new RuntimeException('بداية الجزء الحالي من الجلسة غير متاحة.');
            }

            $elapsedFromSessionStart = (int) ceil((float) $baseStartTime->diffInMinutes(now()));
            if ($totalMinutes <= $elapsedFromSessionStart) {
                throw new RuntimeException('لا يمكن تحديد وقت أقل من أو يساوي الوقت المنقضي بالفعل.');
            }

            $targetEndTime = $baseStartTime->copy()->addMinutes($totalMinutes);
            if ($targetEndTime->lessThanOrEqualTo(now()->addMinutes(5))) {
                throw new RuntimeException('يجب أن يكون وقت النهاية بعد الوقت الحالي بـ 5 دقيقة على الأقل.');
            }

            $elapsedBeforeCurrent = (int) ceil((float) $baseStartTime->diffInMinutes($currentSegmentStart));
            $remainMinutes = $totalMinutes - $elapsedBeforeCurrent;
            if ($remainMinutes <= 0) {
                throw new RuntimeException('الوقت المتبقي غير صالح لتحديد الجلسة.');
            }

            $playTypeName = trim((string) $currentDetail->play_type);
            if ($playTypeName === '') {
                throw new RuntimeException('نوع اللعب الحالي غير صالح.');
            }

            $roundedRemainMinutes = $this->roundMinutesForSwitchPlay((float) $remainMinutes);
            $playPrice = $this->resolvePlayPriceByTypeName($device, $playTypeName, $roundedRemainMinutes, $currentSegmentStart);
            $costRaw = ($playPrice / 60) * $roundedRemainMinutes;
            $cost = $this->roundMoneyForSwitchPlay($costRaw);

            $currentDetail->update([
                'end_time' => $currentSegmentStart->copy()->addMinutes($remainMinutes),
                'period' => $roundedRemainMinutes,
                'cost' => $cost,
                'play_price' => $playPrice,
                'play_category' => 'محدد',
            ]);

            return $currentDetail->fresh();
        });
    }

    private function resolvePlayPriceByTypeName(Devices $device, string $playTypeName, int $period, CarbonInterface $atTime): float
    {
        $playPrice = PlayPrice::query()
            ->where('device_id', $device->id)
            ->whereHas('playType', function ($query) use ($playTypeName): void {
                $query->where('name', $playTypeName);
            })
            ->orderByRaw('CASE WHEN start_from <= ? THEN 0 ELSE 1 END', [$atTime->format('H:i:s')])
            ->orderByDesc('start_from')
            ->first();

        $basePrice = $playPrice ? (float) $playPrice->price : 0.0;

        $offer = Offer::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->first(function (Offer $item) use ($device, $playTypeName, $period, $atTime): bool {
                if (! $this->offerMatchesDevice($item, $device->name)) {
                    return false;
                }

                if (! $this->offerMatchesPlayType($item, $playTypeName)) {
                    return false;
                }

                if (! $this->offerMatchesPeriod($item, $period)) {
                    return false;
                }

                return $this->offerMatchesTime($item, $atTime);
            });

        if ($offer && is_numeric($offer->price)) {
            return round((float) $offer->price, 2);
        }

        return round($basePrice, 2);
    }

    private function offerMatchesDevice(Offer $offer, string $deviceName): bool
    {
        $devicesCsv = trim((string) ($offer->devices ?? ''));
        if ($devicesCsv === '') {
            return true;
        }

        $target = mb_strtolower(trim($deviceName));
        $devices = collect(explode(',', $devicesCsv))
            ->map(static fn (string $name): string => mb_strtolower(trim($name)))
            ->filter(static fn (string $name): bool => $name !== '');

        return $devices->contains($target);
    }

    private function offerMatchesPlayType(Offer $offer, string $playTypeName): bool
    {
        $offerPlayType = trim((string) ($offer->play_type ?? ''));
        if ($offerPlayType === '') {
            return true;
        }

        return mb_strtolower($offerPlayType) === mb_strtolower($playTypeName);
    }

    private function offerMatchesPeriod(Offer $offer, int $period): bool
    {
        $offerPeriod = (int) ($offer->period ?? 0);
        if ($offerPeriod <= 0) {
            return true;
        }

        return $offerPeriod === $period;
    }

    private function offerMatchesTime(Offer $offer, CarbonInterface $now): bool
    {
        $from = trim((string) ($offer->time_from ?? ''));
        $to = trim((string) ($offer->time_to ?? ''));

        if ($from === '' || $to === '') {
            return true;
        }

        $current = $now->format('H:i:s');

        if ($from <= $to) {
            return $current >= $from && $current <= $to;
        }

        return $current >= $from || $current <= $to;
    }

    private function roundMinutesForSwitchPlay(float $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $active = $this->toBool(SystemConfig::get('round_minutes_active', '0'));
        if (! $active) {
            return (int) ceil($minutes);
        }

        $step = (int) SystemConfig::get(
            'round_minutes_s_play',
            SystemConfig::get('round_minutes_add', 1)
        );

        if ($step <= 0) {
            return (int) ceil($minutes);
        }

        $roundUp = $this->toBool(SystemConfig::get('round_minutes_up', '0'));
        $blocks = $minutes / $step;
        $roundedBlocks = $roundUp ? ceil($blocks) : floor($blocks);
        $result = (int) ($roundedBlocks * $step);

        return $result > 0 ? $result : $step;
    }

    private function roundMoneyForSwitchPlay(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $active = $this->toBool(SystemConfig::get('round_money_active', '0'));
        if (! $active) {
            return round($amount, 2);
        }

        $step = (float) SystemConfig::get(
            'round_money_s_play',
            SystemConfig::get('round_money_add', 0)
        );

        if ($step <= 0) {
            return round($amount, 2);
        }

        $roundUp = $this->toBool(SystemConfig::get('round_money_up', '0'));
        $blocks = $amount / $step;
        $roundedBlocks = $roundUp ? ceil($blocks) : floor($blocks);

        return round(max(0, $roundedBlocks * $step), 2);
    }

    private function toBool(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }
}
