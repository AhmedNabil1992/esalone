<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig as Setting;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AddCustomerCreditAction
{
    /**
     * @param  array{customer_id: int, type: string, amount: float|string, description: ?string, cash_box_id?: int|null}  $payload
     */
    public function execute(array $payload, User $actor): CustomerCredit
    {
        return DB::transaction(function () use ($payload, $actor): CustomerCredit {

            $cashBoxId = (int) ($payload['cash_box_id'] ?? 0);

            if ($cashBoxId <= 0) {
                $cashBoxId = (int) Setting::get('sys-default-cashbox', 0);
            }

            $cashBox = CashBox::query()
                ->whereKey($cashBoxId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $cashBox) {
                throw new RuntimeException('يرجى اختيار خزنة نشطة لتسجيل حركة رصيد العميل.');
            }

            /** @var Customer $customer */
            $customer = Customer::query()
                ->where('id', $payload['customer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $amount = (float) $payload['amount'];

            if ($amount <= 0) {
                throw new RuntimeException('المبلغ يجب أن يكون أكبر من الصفر.');
            }

            $balanceBefore = (float) $customer->balance;

            if ($payload['type'] === 'withdraw' && $balanceBefore < $amount) {
                throw new RuntimeException('رصيد العميل غير كافٍ للسحب.');
            }

            $balanceAfter = $payload['type'] === 'deposit'
                ? $balanceBefore + $amount
                : $balanceBefore - $amount;

            $cashBoxBalanceAfter = $payload['type'] === 'deposit'
                ? (float) $cashBox->current_balance + $amount
                : (float) $cashBox->current_balance - $amount;

            if ($payload['type'] === 'withdraw' && $cashBoxBalanceAfter < 0) {
                throw new RuntimeException('رصيد الخزنة غير كافٍ لإتمام عملية السحب.');
            }

            $credit = CustomerCredit::create([
                'customer_id' => $customer->id,
                'user_id' => $actor->id,
                'type' => $payload['type'],
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => $payload['description'] ?? null,
            ]);

            $customer->update(['balance' => $balanceAfter]);

            $cashBox->update([
                'current_balance' => PostPurchaseAction::roundCurrency($cashBoxBalanceAfter),
            ]);

            CashBoxLedgerEntry::query()->create([
                'shift_id' => Shift::currentOpenShiftId(),
                'cash_box_id' => $cashBox->id,
                'created_by_user_id' => $actor->id,
                'reference_type' => CustomerCredit::class,
                'reference_id' => $credit->id,
                'direction' => $payload['type'] === 'deposit' ? 'in' : 'out',
                'operation' => $payload['type'] === 'deposit' ? 'customer_credit_deposit' : 'customer_credit_withdraw',
                'amount' => $amount,
                'description' => $payload['description'] ?? null,
                'occurred_at' => now(),
            ]);

            return $credit;
        });
    }
}
