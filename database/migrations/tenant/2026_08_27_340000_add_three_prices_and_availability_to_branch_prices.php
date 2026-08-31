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
        if (Schema::hasTable('catalog_item_branch_prices')) {
            Schema::table('catalog_item_branch_prices', function (Blueprint $table): void {
                if (! Schema::hasColumn('catalog_item_branch_prices', 'direct_sale_price')) {
                    $table->decimal('direct_sale_price', 12, 2)->nullable()->after('sale_price');
                }
                if (! Schema::hasColumn('catalog_item_branch_prices', 'device_sale_price')) {
                    $table->decimal('device_sale_price', 12, 2)->nullable()->after('direct_sale_price');
                }
                if (! Schema::hasColumn('catalog_item_branch_prices', 'table_sale_price')) {
                    $table->decimal('table_sale_price', 12, 2)->nullable()->after('device_sale_price');
                }
                if (! Schema::hasColumn('catalog_item_branch_prices', 'is_available')) {
                    $table->boolean('is_available')->default(true)->after('table_sale_price');
                }
            });

            DB::statement('
                UPDATE catalog_item_branch_prices
                SET direct_sale_price = COALESCE(direct_sale_price, sale_price),
                    device_sale_price = COALESCE(device_sale_price, sale_price),
                    table_sale_price  = COALESCE(table_sale_price, sale_price)
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catalog_item_branch_prices')) {
            Schema::table('catalog_item_branch_prices', function (Blueprint $table): void {
                $columnsToDrop = array_filter([
                    Schema::hasColumn('catalog_item_branch_prices', 'direct_sale_price') ? 'direct_sale_price' : null,
                    Schema::hasColumn('catalog_item_branch_prices', 'device_sale_price') ? 'device_sale_price' : null,
                    Schema::hasColumn('catalog_item_branch_prices', 'table_sale_price') ? 'table_sale_price' : null,
                    Schema::hasColumn('catalog_item_branch_prices', 'is_available') ? 'is_available' : null,
                ]);

                if (! empty($columnsToDrop)) {
                    $table->dropColumn(array_values($columnsToDrop));
                }
            });
        }
    }
};
