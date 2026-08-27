<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostExpenseAction
{
    /**
     * @param  array{
     *   cash_box_id:int,
     *   expenses_id?:int|null,
     *   amount:numeric-string|int|float,
     *   description?:string|null,
     *   occurred_at?:string|null,
     *   affects_profit?:bool|null,
     *   shift_id?:int|null
     * }  $payload
     */
    public function execute(array $payload, ?User $actor = null): ExpenseHistory
    {
        $amount = (float) ($payload['amount'] ?? 0);

        if ($amount <= 0) {
            throw new InvalidArgumentException('Expense amount must be greater than zero.');
        }

        $cashBox = CashBox::query()
            ->whereKey($payload['cash_box_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if (! $cashBox) {
            throw new InvalidArgumentException('An active cash box is required for expenses.');
        }

        $shift = ! empty($payload['shift_id'])
            ? Shift::query()->whereKey($payload['shift_id'])->whereNull('shift_to')->first()
            : Shift::query()->where('branch_id', $cashBox->branch_id)->open()->active()->orderByDesc('shift_no')->first()
            ?? Shift::currentOpenShift();

        if (! $shift) {
            throw new InvalidArgumentException('An active open shift is required for expenses.');
        }

        return DB::transaction(function () use ($payload, $actor, $amount, $shift, $cashBox): ExpenseHistory {
            $lockedCashBox = CashBox::query()
                ->whereKey($cashBox->id)
                ->lockForUpdate()
                ->first() ?? $cashBox;

            $transaction = ExpenseHistory::query()->create([
                'branch_id' => $cashBox->branch_id ?? $shift->branch_id,
                'shift_id' => $shift->id,
                'cash_box_id' => $lockedCashBox->id,
                'created_by_user_id' => $actor?->id,
                'expenses_id' => $payload['expenses_id'] ?? null,
                'reference_type' => null,
                'reference_id' => null,
                'direction' => 'out',
                'category' => 'expense',
                'amount' => $amount,
                'affects_profit' => (bool) ($payload['affects_profit'] ?? true),
                'description' => $payload['description'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ]);

            $lockedCashBox->current_balance = PostPurchaseAction::roundCurrency((float) $lockedCashBox->current_balance - $amount);
            $lockedCashBox->save();

            CashBoxLedgerEntry::query()->create([
                'branch_id' => $cashBox->branch_id ?? $shift->branch_id,
                'shift_id' => $shift->id,
                'cash_box_id' => $lockedCashBox->id,
                'created_by_user_id' => $actor?->id,
                'reference_type' => ExpenseHistory::class,
                'reference_id' => $transaction->id,
                'direction' => 'out',
                'operation' => 'expense',
                'amount' => $amount,
                'description' => $payload['description'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ]);

            $lockedShift = Shift::query()->whereKey($shift->id)->lockForUpdate()->first();

            if ($lockedShift) {
                $lockedShift->expenses_amount = PostPurchaseAction::roundCurrency((float) $lockedShift->expenses_amount + $amount);
                $lockedShift->save();
            }

            return $transaction->load(['cashBox', 'createdBy']);
        });
    }
}
