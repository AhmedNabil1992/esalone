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

            DB::table('cash_box_ledger_entries')
                ->select(['id', 'cash_box_id', 'shift_id'])
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $branchId = DB::table('cash_boxes')->where('id', $row->cash_box_id)->value('branch_id')
                            ?? DB::table('shifts')->where('id', $row->shift_id)->value('branch_id')
                            ?? 1;

                        DB::table('cash_box_ledger_entries')->where('id', $row->id)->update(['branch_id' => $branchId]);
                    }
                });
        }

        if (Schema::hasTable('invoice_payments') && ! Schema::hasColumn('invoice_payments', 'branch_id')) {
            Schema::table('invoice_payments', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            DB::table('invoice_payments')
                ->select(['id', 'invoice_id', 'cash_box_id'])
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $branchId = DB::table('invoices')->where('id', $row->invoice_id)->value('branch_id')
                            ?? DB::table('cash_boxes')->where('id', $row->cash_box_id)->value('branch_id')
                            ?? 1;

                        DB::table('invoice_payments')->where('id', $row->id)->update(['branch_id' => $branchId]);
                    }
                });
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
