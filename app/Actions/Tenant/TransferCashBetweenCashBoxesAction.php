<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferCashBetweenCashBoxesAction
{
    /**
     * @param  array{from_cash_box_id:int, to_cash_box_id:int, amount:float|int|string, description?:string|null}  $payload
     * @return array{out:CashBoxLedgerEntry,in:CashBoxLedgerEntry}
     */
    public function execute(array $payload, User $actor): array
    {
        return DB::transaction(function () use ($payload, $actor): array {
            $fromCashBoxId = (int) ($payload['from_cash_box_id'] ?? 0);
            $toCashBoxId = (int) ($payload['to_cash_box_id'] ?? 0);
            $amount = (float) ($payload['amount'] ?? 0);

            if ($fromCashBoxId <= 0 || $toCashBoxId <= 0) {
                throw new RuntimeException('يرجى اختيار خزنتين للتحويل.');
            }

            if ($fromCashBoxId === $toCashBoxId) {
                throw new RuntimeException('لا يمكن التحويل إلى نفس الخزنة.');
            }

            if ($amount <= 0) {
                throw new RuntimeException('قيمة التحويل يجب أن تكون أكبر من الصفر.');
            }

            $fromCashBox = CashBox::query()
                ->whereKey($fromCashBoxId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            $toCashBox = CashBox::query()
                ->whereKey($toCashBoxId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $fromCashBox || ! $toCashBox) {
                throw new RuntimeException('يرجى اختيار خزنتين نشطتين.');
            }

            if ((float) $fromCashBox->current_balance < $amount) {
                throw new RuntimeException('رصيد الخزنة المحولة منها غير كافٍ لإتمام التحويل.');
            }

            $fromCashBox->update([
                'current_balance' => PostPurchaseAction::roundCurrency((float) $fromCashBox->current_balance - $amount),
            ]);

            $toCashBox->update([
                'current_balance' => PostPurchaseAction::roundCurrency((float) $toCashBox->current_balance + $amount),
            ]);

            $description = trim((string) ($payload['description'] ?? ''));
            $finalDescription = $description !== ''
                ? $description
                : sprintf('تحويل من خزنة %s إلى خزنة %s', $fromCashBox->name, $toCashBox->name);

            $shiftId = Shift::currentOpenShiftId();

            $outEntry = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $fromCashBox->id,
                'created_by_user_id' => $actor->id,
                'reference_type' => CashBox::class,
                'reference_id' => $toCashBox->id,
                'direction' => 'out',
                'operation' => 'transfer',
                'amount' => $amount,
                'description' => $finalDescription,
                'occurred_at' => now(),
            ]);

            $inEntry = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $toCashBox->id,
                'created_by_user_id' => $actor->id,
                'reference_type' => CashBox::class,
                'reference_id' => $fromCashBox->id,
                'direction' => 'in',
                'operation' => 'transfer',
                'amount' => $amount,
                'description' => $finalDescription,
                'occurred_at' => now(),
            ]);

            return [
                'out' => $outEntry,
                'in' => $inEntry,
            ];
        });
    }
}
