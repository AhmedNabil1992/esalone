<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('play_history') && Schema::hasTable('invoices')) {
            DB::statement('
                UPDATE play_history
                INNER JOIN invoices ON play_history.invoice_no = invoices.invoice_no
                SET play_history.shift_id = invoices.shift_id
                WHERE invoices.shift_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
