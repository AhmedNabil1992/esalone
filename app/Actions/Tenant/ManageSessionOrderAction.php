<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

final class ManageSessionOrderAction
{
    public const string UPDATE_PERMISSION = 'Update:DeviceControl';

    public const string DELETE_PERMISSION = 'Delete:DeviceControl';

    public function updateQuantity(int $orderId, int $quantity, User $actor): OrderSessionDetail
    {
        $this->authorize($actor, self::UPDATE_PERMISSION);

        if ($quantity < 1) {
            throw new InvalidArgumentException('الكمية يجب أن تكون واحدًا على الأقل.');
        }

        return DB::transaction(function () use ($orderId, $quantity): OrderSessionDetail {
            $order = $this->findActiveOrder($orderId);
            $order->update([
                'quantity' => $quantity,
                'total' => round((float) $order->price * $quantity, 2),
                'printed' => false,
            ]);

            return $order->refresh();
        });
    }

    public function delete(int $orderId, User $actor): void
    {
        $this->authorize($actor, self::DELETE_PERMISSION);

        DB::transaction(function () use ($orderId): void {
            $this->findActiveOrder($orderId)->delete();
        });
    }

    private function authorize(User $actor, string $permission): void
    {
        if (! $actor->can($permission)) {
            throw new AuthorizationException('غير مصرح لك بتنفيذ هذا الإجراء.');
        }
    }

    private function findActiveOrder(int $orderId): OrderSessionDetail
    {
        $order = OrderSessionDetail::query()
            ->whereKey($orderId)
            ->where('status', 'pending')
            ->whereHas('playSession', fn ($query) => $query->where('status', 'active'))
            ->lockForUpdate()
            ->first();

        if (! $order instanceof OrderSessionDetail) {
            throw new RuntimeException('لا يمكن تعديل طلب غير تابع لجلسة نشطة.');
        }

        return $order;
    }
}
