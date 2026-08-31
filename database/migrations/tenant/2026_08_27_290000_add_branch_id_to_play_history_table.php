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
            DB::statement('
                UPDATE play_history
                LEFT JOIN invoices ON play_history.invoice_no = invoices.invoice_no
                LEFT JOIN devices ON play_history.device_id = devices.id
                LEFT JOIN shifts ON play_history.shift_id = shifts.id
                SET play_history.branch_id = COALESCE(invoices.branch_id, devices.branch_id, shifts.branch_id, 1)
            ');
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
