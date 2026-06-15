<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

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
    public function execute(int $deviceId, int $playPriceId, User $actor): PlaySessions
    {
        return DB::transaction(function () use ($deviceId, $playPriceId, $actor): PlaySessions {

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

            // إنشاء الجلسة الأم
            /** @var PlaySessions $session */
            $session = PlaySessions::create([
                'device_id' => $deviceId,
                'status' => 'active',
            ]);

            // إنشاء التفاصيل
            PlaySessionDetail::create([
                'play_session_id' => $session->id,
                'device_id' => $deviceId,
                'play_type' => $playPrice->playType?->name ?? '',
                'play_category' => 'مفتوح',
                'play_price' => $playPrice->price,
                'start_time' => now(),
                'end_time' => null,
                'period' => null,
                'cost' => null,
                'status' => 'مشغول',
                'kind' => 'Time',
                'user_id' => $actor->id,
                'shift_id' => $shiftId,
            ]);

            // تحديث حالة الجهاز إلى مشغول
            $device->update(['status' => 'busy']);

            return $session;
        });
    }
}
