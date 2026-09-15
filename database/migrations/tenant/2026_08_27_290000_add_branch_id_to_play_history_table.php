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
        if (Schema::hasTable('play_history') && ! Schema::hasColumn('play_history', 'branch_id')) {
            Schema::table('play_history', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });

            // Backfill branch_id from invoices, devices, or shifts, fallback to Main Branch (1)
            DB::table('play_history')
                ->select(['id', 'invoice_no', 'device_id', 'shift_id'])
                ->orderBy('id')
                ->chunkById(500, function ($rows): void {
                    foreach ($rows as $row) {
                        $branchId = DB::table('invoices')->where('invoice_no', $row->invoice_no)->value('branch_id')
                            ?? DB::table('devices')->where('id', $row->device_id)->value('branch_id')
                            ?? DB::table('shifts')->where('id', $row->shift_id)->value('branch_id')
                            ?? 1;

                        DB::table('play_history')->where('id', $row->id)->update(['branch_id' => $branchId]);
                    }
                });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('play_history') && Schema::hasColumn('play_history', 'branch_id')) {
            Schema::table('play_history', function (Blueprint $table): void {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
