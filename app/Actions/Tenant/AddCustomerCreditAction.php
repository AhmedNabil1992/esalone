<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AddCustomerCreditAction
{
    /**
     * @param  array{customer_id: int, type: string, amount: float|string, description: ?string}  $payload
     */
    public function execute(array $payload, User $actor): CustomerCredit
    {
        return DB::transaction(function () use ($payload, $actor): CustomerCredit {

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

            return $credit;
        });
    }
}
