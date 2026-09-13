<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RedeemCustomerPointsAction
{
    public function execute(int $customerId, float $pointsToRedeem): CustomerCredit
    {
        abort_unless(tenancy()->initialized, 403);

        return DB::connection('tenant')->transaction(function () use ($customerId, $pointsToRedeem): CustomerCredit {
            $customer = Customer::on('tenant')->whereKey($customerId)->lockForUpdate()->firstOrFail();
            abort_unless($customer->is_active, 403);

            if (! is_finite($pointsToRedeem) || $pointsToRedeem < 1 || $pointsToRedeem > (float) $customer->points) {
                throw ValidationException::withMessages(['points' => 'النقاط المطلوبة أكبر من الرصيد المتاح أو غير صالحة.']);
            }

            $rate = (float) SystemConfig::on('tenant')->where('key', 'sys_customer_points_redeem_ratio')->value('value');
            if (! is_finite($rate) || $rate <= 0) {
                $rate = 10;
            }
            $convertiblePoints = floor($pointsToRedeem / $rate) * $rate;
            if ($convertiblePoints <= 0) {
                throw ValidationException::withMessages(['points' => "أقل عدد نقاط يمكن استبداله هو {$rate}."]);
            }

            $moneyToAdd = round($convertiblePoints / $rate, 2);
            $balanceBefore = (float) $customer->balance;
            $balanceAfter = round($balanceBefore + $moneyToAdd, 2);
            $customer->update([
                'points' => round((float) $customer->points - $convertiblePoints, 2),
                'balance' => $balanceAfter,
            ]);

            return CustomerCredit::on('tenant')->create([
                'customer_id' => $customer->id,
                'user_id' => auth()->guard('web')->id() ?? User::on('tenant')->orderBy('id')->value('id'),
                'type' => 'redeem',
                'amount' => $moneyToAdd,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'تحويل نقاط العميل إلى رصيد ('.$convertiblePoints.' نقطة) عبر البوابة الإلكترونية',
            ]);
        }, 3);
    }
}
