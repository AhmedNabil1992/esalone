<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdjustCashBoxBalanceAction
{
    /**
     * @param  array{cash_box_id:int, direction:string, reason_type:string, amount:float|int|string, description?:string|null}  $payload
     * @return array{cashBox:CashBox,ledgerEntry:CashBoxLedgerEntry}
     */
    public function execute(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor): array {
            $cashBoxId = (int) ($payload['cash_box_id'] ?? 0);
            $direction = (string) ($payload['direction'] ?? 'out');
            $reasonType = (string) ($payload['reason_type'] ?? 'adjustment');
            $amount = (float) ($payload['amount'] ?? 0);

            if ($cashBoxId <= 0) {
                throw new RuntimeException('يرجى اختيار الخزنة.');
            }

            if (! in_array($direction, ['in', 'out'], true)) {
                throw new RuntimeException('اتجاه الحركة غير صحيح.');
            }

            if ($amount <= 0) {
                throw new RuntimeException('قيمة التعديل يجب أن تكون أكبر من الصفر.');
            }

            $cashBox = CashBox::query()
                ->whereKey($cashBoxId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $cashBox) {
                throw new RuntimeException('الخزنة المختارة غير موجودة أو غير نشطة.');
            }

            $currentBalance = (float) $cashBox->current_balance;

            if ($direction === 'out' && $currentBalance < $amount) {
                throw new RuntimeException(sprintf(
                    'رصيد الخزنة الحالي (%.2f ج.م) غير كافٍ لإتمام عملية الخصم/السحب (%.2f ج.م).',
                    $currentBalance,
                    $amount
                ));
            }

            $newBalance = match ($direction) {
                'in' => round($currentBalance + $amount, 2),
                'out' => round($currentBalance - $amount, 2),
            };

            $cashBox->update([
                'current_balance' => $newBalance,
            ]);

            $reasonText = match ($reasonType) {
                'owner_withdrawal' => 'مسحوبات مالك (سحب صاحب المحل)',
                'shift_deficit' => 'تسوية عجز إيراد / وردية',
                'shift_surplus' => 'تسوية زيادة إيراد / وردية',
                'cashbox_adjustment' => 'تسوية رصيد خزانة',
                default => 'تسوية رصيد',
            };

            $userDescription = trim((string) ($payload['description'] ?? ''));
            $finalDescription = $userDescription !== ''
                ? sprintf('%s - %s', $reasonText, $userDescription)
                : $reasonText;

            $operation = match ($reasonType) {
                'owner_withdrawal' => 'owner_withdrawal',
                'shift_deficit' => 'shift_deficit',
                'shift_surplus' => 'shift_surplus',
                default => 'adjustment',
            };

            $shiftId = Shift::currentOpenShiftId();

            $ledgerEntry = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $cashBox->id,
                'created_by_user_id' => $actor->id,
                'reference_type' => CashBox::class,
                'reference_id' => $cashBox->id,
                'direction' => $direction,
                'operation' => $operation,
                'amount' => $amount,
                'description' => $finalDescription,
                'occurred_at' => now(),
            ]);

            return [
                'cashBox' => $cashBox,
                'ledgerEntry' => $ledgerEntry,
            ];
        });
    }
}
