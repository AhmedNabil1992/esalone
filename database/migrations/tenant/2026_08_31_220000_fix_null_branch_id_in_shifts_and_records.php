<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultBranchId = DB::table('branches')->where('is_active', true)->orderBy('id')->value('id') ?? 1;

        $tables = [
            'shifts',
            'invoices',
            'cash_box_ledger_entries',
            'expense_histories',
            'purchases',
            'stock_movements',
            'market_history',
            'play_history',
            'tax_histories',
            'service_histories',
            'discount_history',
            'customer_credits',
            'invoice_payments',
            'cash_boxes',
            'warehouses',
            'devices',
            'offers',
            'reservations',
            'waiting_lists',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                DB::table($table)->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data healing
    }
};
