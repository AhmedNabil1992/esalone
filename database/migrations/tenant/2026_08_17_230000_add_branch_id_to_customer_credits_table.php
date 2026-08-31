<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_credits') && ! Schema::hasColumn('customer_credits', 'branch_id')) {
            Schema::table('customer_credits', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            // Backfill existing customer_credits records to default Main Branch (ID 1)
            DB::table('customer_credits')->whereNull('branch_id')->update(['branch_id' => 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_credits') && Schema::hasColumn('customer_credits', 'branch_id')) {
            Schema::table('customer_credits', function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
