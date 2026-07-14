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
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->decimal('amount', 12, 4)->default(0.0000)->after('quantity');
        });

        Schema::table('market_history', function (Blueprint $table) {
            $table->decimal('unit_cost', 12, 4)->default(0.0000)->after('unit_price');
            $table->decimal('total_cost', 12, 4)->default(0.0000)->after('total_amount');
        });

        try {
            // Populate existing stocks' amount as qty * purchase_price
            DB::table('warehouse_stocks')
                ->update([
                    'amount' => DB::raw('quantity * (SELECT COALESCE(purchase_price, 0) FROM catalog_items WHERE catalog_items.id = warehouse_stocks.catalog_item_id LIMIT 1)'),
                ]);

            // Populate existing market histories' cost as purchase_price
            DB::table('market_history')
                ->update([
                    'unit_cost' => DB::raw('(SELECT COALESCE(purchase_price, 0) FROM catalog_items WHERE catalog_items.id = market_history.catalog_item_id LIMIT 1)'),
                    'total_cost' => DB::raw('quantity * (SELECT COALESCE(purchase_price, 0) FROM catalog_items WHERE catalog_items.id = market_history.catalog_item_id LIMIT 1)'),
                ]);
        } catch (Exception $e) {
            // Silence if tables are empty/unavailable
        }
    }

    public function down(): void
    {
        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->dropColumn('amount');
        });

        Schema::table('market_history', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });
    }
};
