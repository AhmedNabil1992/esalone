<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\FinancialTransaction;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostExpenseAction
{
    /**
     * @param  array{
     *   cash_box_id:int,
     *   amount:numeric-string|int|float,
     *   description?:string|null,
     *   occurred_at?:string|null,
     *   affects_profit?:bool|null
     * }  $payload
     */
    public function execute(array $payload, ?User $actor = null): FinancialTransaction
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

        $shift = Shift::currentOpenShift();

        if (! $shift) {
            throw new InvalidArgumentException('An active open shift is required for expenses.');
        }

        return DB::transaction(function () use ($payload, $actor, $amount, $shift): FinancialTransaction {
            $cashBox = CashBox::query()
                ->whereKey($payload['cash_box_id'])
                ->lockForUpdate()
                ->first();

            if (! $cashBox) {
                throw new InvalidArgumentException('Cash box not found.');
            }

            $transaction = FinancialTransaction::query()->create([
                'cash_box_id' => $cashBox->id,
                'created_by_user_id' => $actor?->id,
                'reference_type' => null,
                'reference_id' => null,
                'direction' => 'out',
                'category' => 'expense',
                'amount' => $amount,
                'affects_profit' => (bool) ($payload['affects_profit'] ?? true),
                'description' => $payload['description'] ?? null,
                'occurred_at' => $payload['occurred_at'] ?? now(),
            ]);

            $cashBox->current_balance = PostPurchaseAction::roundCurrency((float) $cashBox->current_balance - $amount);
            $cashBox->save();

            $lockedShift = Shift::query()->whereKey($shift->id)->lockForUpdate()->first();

            if ($lockedShift) {
                $lockedShift->expenses_amount = PostPurchaseAction::roundCurrency((float) $lockedShift->expenses_amount + $amount);
                $lockedShift->save();
            }

            return $transaction->load(['cashBox', 'createdBy']);
        });
    }
}
