<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Devices;
use App\Models\Tenant\Offer;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SwitchPlayAction
{
    /**
     * تبديل نوع اللعب داخل نفس الجلسة النشطة.
     *
     * @throws RuntimeException
     */
    public function execute(int $deviceId, int $newPlayPriceId, User $actor): PlaySessionDetail
    {
        return DB::transaction(function () use ($deviceId, $newPlayPriceId, $actor): PlaySessionDetail {
            /** @var Devices $device */
            $device = Devices::query()
                ->whereKey($deviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($device->status !== 'busy') {
                throw new RuntimeException('لا يمكن تبديل اللعب لأن الجهاز غير مشغول حالياً.');
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
                throw new RuntimeException('لا توجد بيانات جلسة نشطة للتبديل.');
            }

            /** @var PlayPrice $newPlayPrice */
            $newPlayPrice = PlayPrice::query()
                ->with('playType')
                ->where('device_id', $device->id)
                ->findOrFail($newPlayPriceId);

            $newPlayTypeName = trim((string) ($newPlayPrice->playType?->name ?? ''));
            $currentPlayTypeName = trim((string) $currentDetail->play_type);

            if ($newPlayTypeName === '') {
                throw new RuntimeException('نوع اللعب الجديد غير صالح.');
            }

            if ($newPlayTypeName === $currentPlayTypeName) {
                throw new RuntimeException('تم اختيار نفس نوع اللعب الحالي.');
            }

            $now = now();
            $oldEndTime = $currentDetail->end_time;

            if ($oldEndTime && $now->greaterThanOrEqualTo($oldEndTime)) {
                throw new RuntimeException('انتهى وقت الجلسة الحالية، لا يمكن التبديل الآن.');
            }

            $elapsedSeconds = max(0, $currentDetail->start_time?->diffInSeconds($now) ?? 0);
            $elapsedMinutesRaw = $elapsedSeconds / 60;

            // نفس سلوك الديسكتوب: منع التبديل قبل مرور 5 دقائق على الأقل.
            // if ($elapsedMinutesRaw < 5) {
            //     throw new RuntimeException('لا يمكن تبديل اللعب قبل مرور 5 دقائق من بداية النوع الحالي.');
            // }

            $segmentPeriod = $this->roundMinutesForSwitchPlay($elapsedMinutesRaw);
            $segmentCostRaw = ($segmentPeriod / 60) * (float) $currentDetail->play_price;
            $segmentCost = $this->roundMoneyForSwitchPlay($segmentCostRaw);

            $currentDetail->update([
                'end_time' => $now,
                'period' => $segmentPeriod,
                'cost' => $segmentCost,
                'status_img' => 'مغلق',
            ]);

            $shiftId = Shift::currentOpenShiftId();
            if (! $shiftId) {
                throw new RuntimeException('لا توجد وردية مفتوحة. يرجى فتح وردية أولاً.');
            }

            $nextCategory = $oldEndTime ? 'محدد' : 'مفتوح';
            $nextEndTime = null;
            $nextPeriod = null;

            if ($oldEndTime) {
                $remainingSeconds = max(0, $now->diffInSeconds($oldEndTime, false));
                if ($remainingSeconds <= 0) {
                    throw new RuntimeException('الوقت المتبقي غير كافٍ لإتمام التبديل.');
                }

                $nextEndTime = $oldEndTime;
                $nextPeriod = max(1, (int) ceil($remainingSeconds / 60));
            }

            $nextPrice = $this->resolveSwitchPlayPrice($device, $newPlayPrice, $nextPeriod, $now);

            /** @var PlaySessionDetail $newDetail */
            $newDetail = PlaySessionDetail::query()->create([
                'play_session_id' => $session->id,
                'device_id' => $device->id,
                'play_type' => $newPlayTypeName,
                'play_category' => $nextCategory,
                'play_price' => $nextPrice,
                'start_time' => $now,
                'end_time' => $nextEndTime,
                'period' => $nextPeriod,
                'cost' => null,
                'status_img' => null,
                'status' => 'مشغول',
                'kind' => 'Time',
                'user_id' => $actor->id,
                'shift_id' => $shiftId,
            ]);

            return $newDetail;
        });
    }

    private function resolveSwitchPlayPrice(Devices $device, PlayPrice $selectedPlayPrice, ?int $period, CarbonInterface $now): float
    {
        $selectedPlayType = trim((string) ($selectedPlayPrice->playType?->name ?? ''));

        /** @var Offer|null $offer */
        $offer = Offer::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->first(function (Offer $item) use ($device, $selectedPlayType, $period, $now): bool {
                if (! $this->offerMatchesDevice($item, $device->name)) {
                    return false;
                }

                if (! $this->offerMatchesPlayType($item, $selectedPlayType)) {
                    return false;
                }

                if (! $this->offerMatchesPeriod($item, $period)) {
                    return false;
                }

                return $this->offerMatchesTime($item, $now);
            });

        if ($offer && is_numeric($offer->price)) {
            return round((float) $offer->price, 2);
        }

        return round((float) $selectedPlayPrice->price, 2);
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

    private function offerMatchesPlayType(Offer $offer, string $selectedPlayType): bool
    {
        $offerPlayType = trim((string) ($offer->play_type ?? ''));
        if ($offerPlayType === '') {
            return true;
        }

        return mb_strtolower($offerPlayType) === mb_strtolower($selectedPlayType);
    }

    private function offerMatchesPeriod(Offer $offer, ?int $period): bool
    {
        $offerPeriod = (int) ($offer->period ?? 0);
        if ($offerPeriod <= 0) {
            return true;
        }

        if ($period === null) {
            return false;
        }

        return $period === $offerPeriod;
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

        if ($result <= 0) {
            return $step;
        }

        return $result;
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
