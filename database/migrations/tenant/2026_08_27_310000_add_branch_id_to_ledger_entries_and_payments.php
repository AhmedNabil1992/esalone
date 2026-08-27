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
        if (Schema::hasTable('cash_box_ledger_entries') && ! Schema::hasColumn('cash_box_ledger_entries', 'branch_id')) {
            Schema::table('cash_box_ledger_entries', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            DB::statement('
                UPDATE cash_box_ledger_entries
                LEFT JOIN cash_boxes ON cash_box_ledger_entries.cash_box_id = cash_boxes.id
                LEFT JOIN shifts ON cash_box_ledger_entries.shift_id = shifts.id
                SET cash_box_ledger_entries.branch_id = COALESCE(cash_boxes.branch_id, shifts.branch_id, 1)
            ');
        }

        if (Schema::hasTable('invoice_payments') && ! Schema::hasColumn('invoice_payments', 'branch_id')) {
            Schema::table('invoice_payments', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            DB::statement('
                UPDATE invoice_payments
                LEFT JOIN invoices ON invoice_payments.invoice_id = invoices.id
                LEFT JOIN cash_boxes ON invoice_payments.cash_box_id = cash_boxes.id
                SET invoice_payments.branch_id = COALESCE(invoices.branch_id, cash_boxes.branch_id, 1)
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cash_box_ledger_entries') && Schema::hasColumn('cash_box_ledger_entries', 'branch_id')) {
            Schema::table('cash_box_ledger_entries', function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('invoice_payments') && Schema::hasColumn('invoice_payments', 'branch_id')) {
            Schema::table('invoice_payments', function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
