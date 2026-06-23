<?php

declare(strict_types=1);

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

class AddSessionTimeAction
{
    /**
     * إضافة وقت إلى جلسة محددة الوقت.
     *
     * @throws RuntimeException
     */
    public function execute(int $deviceId, int $addedMinutesInput, User $actor): PlaySessionDetail
    {
        return DB::transaction(function () use ($deviceId, $addedMinutesInput, $actor): PlaySessionDetail {
            if ($addedMinutesInput <= 0) {
                throw new RuntimeException('عدد الدقائق يجب أن يكون أكبر من صفر.');
            }

            if ($addedMinutesInput > 999) {
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
                throw new RuntimeException('لا توجد بيانات وقت نشطة لهذا الجهاز.');
            }

            $oldEndTime = $currentDetail->end_time;
            if (! $oldEndTime || $currentDetail->period === null) {
                throw new RuntimeException('لا يمكن إضافة وقت لأن الجلسة وقت مفتوح.');
            }

            $playTypeName = trim((string) $currentDetail->play_type);
            if ($playTypeName === '') {
                throw new RuntimeException('نوع اللعب الحالي غير صالح.');
            }

            $oldStart = $currentDetail->start_time;
            if (! $oldStart) {
                throw new RuntimeException('وقت بداية الجلسة الحالي غير صالح.');
            }

            $oldSessionMinutes = max(1, (int) ceil((float) $oldStart->diffInMinutes($oldEndTime)));
            $oldSessionPrice = $this->resolvePlayPriceByTypeName($device, $playTypeName, $oldSessionMinutes, $oldStart);
            $oldSessionCostRaw = ($oldSessionMinutes * $oldSessionPrice) / 60;
            $oldSessionCost = $this->roundMoneyForAdd($oldSessionCostRaw);

            $currentDetail->update([
                'end_time' => $oldEndTime,
                'period' => $oldSessionMinutes,
                'cost' => $oldSessionCost,
                'play_price' => $oldSessionPrice,
                'status_img' => 'مغلق',
            ]);

            $addedRoundedMinutes = $this->roundMinutesForAdd((float) $addedMinutesInput);
            $newStartTime = $oldEndTime;
            $newEndTime = $oldEndTime->copy()->addMinutes($addedMinutesInput);
            $newPlayPrice = $this->resolvePlayPriceByTypeName($device, $playTypeName, $addedRoundedMinutes, $newStartTime);
            $newCostRaw = ($addedRoundedMinutes * $newPlayPrice) / 60;
            $newCost = $this->roundMoneyForAdd($newCostRaw);

            $shiftId = $currentDetail->shift_id ?: Shift::currentOpenShiftId();
            if (! $shiftId) {
                throw new RuntimeException('لا توجد وردية مفتوحة. يرجى فتح وردية أولاً.');
            }

            /** @var PlaySessionDetail $newDetail */
            $newDetail = PlaySessionDetail::query()->create([
                'play_session_id' => $session->id,
                'device_id' => $device->id,
                'play_type' => $playTypeName,
                'play_category' => 'محدد',
                'play_price' => $newPlayPrice,
                'start_time' => $newStartTime,
                'end_time' => $newEndTime,
                'period' => $addedRoundedMinutes,
                'cost' => $newCost,
                'status_img' => null,
                'status' => 'مشغول',
                'kind' => 'Time',
                'user_id' => $actor->id,
                'shift_id' => $shiftId,
            ]);

            app(DispatchDeviceControlCommandAction::class)->execute(
                $device,
                $actor,
                'ON,Add,'.(string) $addedMinutesInput,
                'Add Session Time'
            );

            return $newDetail;
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

        $basePrice = $playPrice ? (float) $playPrice->price : (float) 0;

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

    private function roundMinutesForAdd(float $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $active = $this->toBool(SystemConfig::get('round_minutes_active', '0'));
        if (! $active) {
            return (int) ceil($minutes);
        }

        $step = (int) SystemConfig::get('round_minutes_add', 1);
        if ($step <= 0) {
            return (int) ceil($minutes);
        }

        $roundUp = $this->toBool(SystemConfig::get('round_minutes_up', '0'));
        $blocks = $minutes / $step;
        $roundedBlocks = $roundUp ? ceil($blocks) : floor($blocks);
        $result = (int) ($roundedBlocks * $step);

        return $result > 0 ? $result : $step;
    }

    private function roundMoneyForAdd(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $active = $this->toBool(SystemConfig::get('round_money_active', '0'));
        if (! $active) {
            return round($amount, 2);
        }

        $step = (float) SystemConfig::get('round_money_add', 0);
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
