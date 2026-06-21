<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'cash_box_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->foreignId('cash_box_id')
                ->nullable()
                ->after('shift_id')
                ->constrained('cash_boxes')
                ->nullOnDelete();

            $table->index(['cash_box_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'cash_box_id')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropIndex('invoices_cash_box_id_issued_at_index');
            $table->dropConstrainedForeignId('cash_box_id');
        });
    }
};
