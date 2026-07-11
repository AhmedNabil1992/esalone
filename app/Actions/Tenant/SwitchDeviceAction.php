<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Devices;
use App\Models\Tenant\Offer;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SwitchDeviceAction
{
    public ?Carbon $customSwitchTime = null;

    /**
     * @throws RuntimeException
     */
    public function execute(int $oldDeviceId, int $newDeviceId, User $actor): PlaySessionDetail
    {
        return DB::transaction(function () use ($oldDeviceId, $newDeviceId, $actor): PlaySessionDetail {
            if ($oldDeviceId === $newDeviceId) {
                throw new RuntimeException('يرجى اختيار جهاز مختلف للتبديل.');
            }

            /** @var Devices $oldDevice */
            $oldDevice = Devices::query()
                ->whereKey($oldDeviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            /** @var Devices $newDevice */
            $newDevice = Devices::query()
                ->whereKey($newDeviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($oldDevice->status !== 'busy') {
                throw new RuntimeException('الجهاز الحالي غير مشغول.');
            }

            if ($newDevice->status !== 'active') {
                throw new RuntimeException('الجهاز الجديد غير متاح الآن.');
            }

            /** @var PlaySessions|null $session */
            $session = PlaySessions::query()
                ->where('device_id', $oldDevice->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $session) {
                throw new RuntimeException('لا توجد جلسة نشطة على الجهاز الحالي.');
            }

            $targetHasActiveSession = PlaySessions::query()
                ->where('device_id', $newDevice->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->exists();

            if ($targetHasActiveSession) {
                throw new RuntimeException('الجهاز الجديد مرتبط بجلسة نشطة بالفعل.');
            }

            /** @var PlaySessionDetail|null $currentDetail */
            $currentDetail = PlaySessionDetail::query()
                ->where('play_session_id', $session->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $currentDetail) {
                throw new RuntimeException('لا توجد تفاصيل جلسة حالية للتبديل.');
            }

            if (! $currentDetail->start_time) {
                throw new RuntimeException('وقت بداية الجلسة الحالية غير متاح.');
            }

            $now = $this->customSwitchTime ?? now();
            $currentPlayType = trim((string) $currentDetail->play_type);
            if ($currentPlayType === '') {
                throw new RuntimeException('نوع اللعب الحالي غير صالح.');
            }

            $elapsedMinutesRaw = max(0.0, (float) $currentDetail->start_time->diffInMinutes($now));
            $elapsedRoundedMinutes = $this->roundMinutesForSwitchDevice($elapsedMinutesRaw);
            $oldResolvedPrice = $this->resolvePlayPriceByTypeName(
                $oldDevice,
                $currentPlayType,
                $elapsedRoundedMinutes,
                $currentDetail->start_time,
            );
            $oldCostRaw = ($elapsedRoundedMinutes * $oldResolvedPrice) / 60;
            $oldCost = $this->roundMoneyForSwitchDevice($oldCostRaw);

            $isTimed = $currentDetail->period !== null && $currentDetail->end_time !== null;
            $newEndTime = null;
            $newPeriod = null;
            $newDeviceBridgeAction = 'ON,Unlimited,0';

            if ($isTimed) {
                $remainingMinutes = (int) floor((float) $now->diffInMinutes($currentDetail->end_time, false));
                if ($remainingMinutes <= 5) {
                    throw new RuntimeException('لا يمكن تبديل الجهاز، المتبقي خمس دقائق أو أقل.');
                }

                $newEndTime = $now->copy()->addMinutes($remainingMinutes);
                $newPeriod = $this->roundMinutesForSwitchDevice((float) $remainingMinutes);
                $newDeviceBridgeAction = 'ON,Limit,'.(string) $remainingMinutes;
            }

            $targetPlayPrice = $this->resolveTargetPlayPrice($newDevice, $currentPlayType);
            $targetPlayTypeName = trim((string) ($targetPlayPrice->playType?->name ?? ''));
            if ($targetPlayTypeName === '') {
                throw new RuntimeException('نوع اللعب على الجهاز الجديد غير صالح.');
            }

            $newResolvedPrice = $this->resolvePlayPriceByTypeName(
                $newDevice,
                $targetPlayTypeName,
                $newPeriod ?? 0,
                $now,
            );

            $currentDetail->update([
                'end_time' => $now,
                'period' => $elapsedRoundedMinutes,
                'cost' => $oldCost,
                'play_price' => $oldResolvedPrice,
                'status_img' => 'مغلق',
            ]);

            $shiftId = $currentDetail->shift_id ?: Shift::currentOpenShiftId();
            if (! $shiftId) {
                throw new RuntimeException('لا توجد وردية مفتوحة. يرجى فتح وردية أولاً.');
            }

            $newCost = $newPeriod === null
                ? null
                : $this->roundMoneyForSwitchDevice((($newPeriod * $newResolvedPrice) / 60));

            /** @var PlaySessionDetail $newDetail */
            $newDetail = PlaySessionDetail::query()->create([
                'play_session_id' => $session->id,
                'device_id' => $newDevice->id,
                'play_type' => $targetPlayTypeName,
                'play_category' => $isTimed ? 'محدد' : 'مفتوح',
                'play_price' => $newResolvedPrice,
                'start_time' => $now,
                'end_time' => $newEndTime,
                'period' => $newPeriod,
                'cost' => $newCost,
                'status_img' => null,
                'status' => 'مشغول',
                'kind' => 'Time',
                'user_id' => $actor->id,
                'shift_id' => $shiftId,
            ]);

            $session->update([
                'device_id' => $newDevice->id,
            ]);

            OrderSessionDetail::query()
                ->where('play_session_id', $session->id)
                ->where('status', 'pending')
                ->update(['device_id' => $newDevice->id]);

            $oldDevice->update(['status' => 'active']);
            $newDevice->update(['status' => 'busy']);

            app(DispatchDeviceControlCommandAction::class)->execute($oldDevice, $actor, 'OFF', 'Switch Device');
            app(DispatchDeviceControlCommandAction::class)->execute($newDevice, $actor, $newDeviceBridgeAction, 'Switch Device');

            return $newDetail;
        });
    }

    private function resolveTargetPlayPrice(Devices $device, string $preferredPlayType): PlayPrice
    {
        $preferred = PlayPrice::query()
            ->with('playType')
            ->where('device_id', $device->id)
            ->whereHas('playType', function ($query) use ($preferredPlayType): void {
                $query->where('name', $preferredPlayType);
            })
            ->orderBy('id')
            ->first();

        if ($preferred) {
            return $preferred;
        }

        $fallback = PlayPrice::query()
            ->with('playType')
            ->where('device_id', $device->id)
            ->orderBy('id')
            ->first();

        if (! $fallback) {
            throw new RuntimeException('لا يوجد تسعير لعب متاح على الجهاز الجديد.');
        }

        return $fallback;
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

    private function roundMinutesForSwitchDevice(float $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $active = $this->toBool(SystemConfig::get('round_minutes_active', '0'));
        if (! $active) {
            return (int) ceil($minutes);
        }

        $step = (int) SystemConfig::get(
            'round_minutes_s_device',
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

    private function roundMoneyForSwitchDevice(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $active = $this->toBool(SystemConfig::get('round_money_active', '0'));
        if (! $active) {
            return round($amount, 2);
        }

        $step = (float) SystemConfig::get(
            'round_money_s_device',
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
