<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Devices;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StartSessionAction
{
    /**
     * فتح جلسة لعب جديدة لجهاز على أساس سعر لعب محدد.
     *
     * @throws RuntimeException
     */
    public function execute(int $deviceId, int $playPriceId, User $actor, ?int $durationMinutes = null, ?int $customerId = null): PlaySessions
    {
        return DB::transaction(function () use ($deviceId, $playPriceId, $actor, $durationMinutes, $customerId): PlaySessions {

            if ($durationMinutes !== null && $durationMinutes <= 0) {
                throw new RuntimeException('مدة اللعب يجب أن تكون أكبر من صفر دقيقة.');
            }

            /** @var Devices $device */
            $device = Devices::query()
                ->where('id', $deviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($device->status === 'busy') {
                throw new RuntimeException('الجهاز مشغول حالياً، لا يمكن فتح جلسة جديدة.');
            }

            /** @var PlayPrice $playPrice */
            $playPrice = PlayPrice::query()
                ->with('playType')
                ->findOrFail($playPriceId);

            if ($playPrice->device_id !== $deviceId) {
                throw new RuntimeException('سعر اللعب لا ينتمي لهذا الجهاز.');
            }

            $shiftId = Shift::currentOpenShiftId();
            if (! $shiftId) {
                throw new RuntimeException('لا توجد وردية مفتوحة. يرجى فتح وردية أولاً.');
            }

            $customer = null;
            if ($customerId !== null) {
                $customer = Customer::query()
                    ->where('is_active', true)
                    ->find($customerId);

                if (! $customer) {
                    throw new RuntimeException('العميل المختار غير متاح أو غير موجود.');
                }
            }

            $startTime = now();
            $isTimedSession = $durationMinutes !== null;
            $endTime = $isTimedSession ? $startTime->copy()->addMinutes($durationMinutes) : null;

            // إنشاء الجلسة الأم
            /** @var PlaySessions $session */
            $session = PlaySessions::create([
                'device_id' => $deviceId,
                'customer_id' => $customer?->id,
                'status' => 'active',
            ]);

            // إنشاء التفاصيل
            PlaySessionDetail::create([
                'play_session_id' => $session->id,
                'device_id' => $deviceId,
                'play_type' => $playPrice->playType?->name ?? '',
                'play_category' => $isTimedSession ? 'محدد' : 'مفتوح',
                'play_price' => $playPrice->price,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'period' => $durationMinutes,
                'cost' => null,
                'status' => 'مشغول',
                'kind' => 'Time',
                'user_id' => $actor->id,
                'shift_id' => $shiftId,
            ]);

            // تحديث حالة الجهاز إلى مشغول
            $device->update(['status' => 'busy']);

            $bridgeAction = $isTimedSession
                ? 'ON,Limit,'.(string) $durationMinutes
                : 'ON,Unlimited,0';

            app(DispatchDeviceControlCommandAction::class)->execute(
                $device,
                $actor,
                $bridgeAction,
                'Start Session'
            );

            return $session;
        });
    }
}
