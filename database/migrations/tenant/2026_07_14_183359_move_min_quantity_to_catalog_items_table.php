<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('catalog_items', function (Blueprint $table) {
            $table->decimal('min_quantity', 10, 3)->default(0.000)->after('is_active');
        });

        try {
            // Copy existing data from warehouse_stocks to catalog_items in a database-agnostic way
            DB::table('catalog_items')
                ->update([
                    'min_quantity' => DB::raw('(SELECT min_quantity FROM warehouse_stocks WHERE warehouse_stocks.catalog_item_id = catalog_items.id LIMIT 1)'),
                ]);
        } catch (Exception $e) {
            // Silence if tables are empty/unavailable
        }

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropColumn('min_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('min_quantity', 10, 3)->default(0.000)->after('quantity');
        });

        try {
            // Copy data back in a database-agnostic way
            DB::table('warehouse_stocks')
                ->update([
                    'min_quantity' => DB::raw('(SELECT min_quantity FROM catalog_items WHERE catalog_items.id = warehouse_stocks.catalog_item_id LIMIT 1)'),
                ]);
        } catch (Exception $e) {
            // Silence if tables are empty/unavailable
        }

        Schema::table('catalog_items', function (Blueprint $table) {
            $table->dropColumn('min_quantity');
        });
    }
};
