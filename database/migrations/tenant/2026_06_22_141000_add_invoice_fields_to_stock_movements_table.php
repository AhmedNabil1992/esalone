<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_movements', 'invoice_id')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->foreignId('invoice_id')
                    ->nullable()
                    ->after('purchase_item_id')
                    ->constrained('invoices')
                    ->nullOnDelete();

                $table->string('invoice_no')
                    ->nullable()
                    ->after('invoice_id')
                    ->index();

                $table->string('invoice_type')
                    ->nullable()
                    ->after('invoice_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_movements', 'invoice_id')) {
            Schema::table('stock_movements', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('invoice_id');
                $table->dropIndex(['invoice_no']);
                $table->dropColumn('invoice_no');
                $table->dropColumn('invoice_type');
            });
        }
    }
};
