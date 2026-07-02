<?php

namespace App\Console\Commands\Tenant;

use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerLevel;
use App\Models\Tenant\Invoice;
use Illuminate\Console\Command;

class UpdateCustomerLevelsCommand extends Command
{
    protected $signature = 'tenants:update-customer-levels';

    protected $description = 'تحديث فئات ومستويات العملاء تلقائياً بناءً على إجمالي الإنفاق والنقاط التراكمية';

    public function handle(): int
    {
        $this->info('بدء تحديث مستويات العملاء...');

        $levels = CustomerLevel::query()
            ->where('is_active', true)
            ->orderByDesc('points_required')
            ->get();

        if ($levels->isEmpty()) {
            $this->warn('لم يتم تعريف أي فئات عملاء نشطة بعد.');

            return self::SUCCESS;
        }

        $customers = Customer::query()->where('is_active', true)->get();

        $updatedCount = 0;

        foreach ($customers as $customer) {
            // حساب إجمالي الإنفاق التاريخي للعميل كمقياس للنقاط التراكمية المكتسبة
            $lifetimeSpent = (float) Invoice::query()
                ->where('customer_id', $customer->id)
                ->where('status', 'posted')
                ->sum('final_amount');

            $matchedLevelId = null;

            foreach ($levels as $level) {
                if ($lifetimeSpent >= (float) $level->points_required) {
                    $matchedLevelId = $level->id;
                    break;
                }
            }

            if ($customer->customer_level_id !== $matchedLevelId) {
                $customer->update([
                    'customer_level_id' => $matchedLevelId,
                ]);
                $updatedCount++;
            }
        }

        $this->info("تم تحديث مستويات {$updatedCount} عميل بنجاح.");

        return self::SUCCESS;
    }
}
