<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('financial_transactions') && ! Schema::hasTable('expense_histories')) {
            Schema::rename('financial_transactions', 'expense_histories');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expense_histories') && ! Schema::hasTable('financial_transactions')) {
            Schema::rename('expense_histories', 'financial_transactions');
        }
    }
};
