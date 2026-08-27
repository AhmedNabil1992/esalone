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
        if (Schema::hasTable('catalog_items')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                if (! Schema::hasColumn('catalog_items', 'direct_sale_price')) {
                    $table->decimal('direct_sale_price', 12, 2)->nullable()->after('sale_price');
                }
                if (! Schema::hasColumn('catalog_items', 'device_sale_price')) {
                    $table->decimal('device_sale_price', 12, 2)->nullable()->after('direct_sale_price');
                }
                if (! Schema::hasColumn('catalog_items', 'table_sale_price')) {
                    $table->decimal('table_sale_price', 12, 2)->nullable()->after('device_sale_price');
                }
            });

            DB::statement('
                UPDATE catalog_items
                SET direct_sale_price = COALESCE(direct_sale_price, sale_price, 0),
                    device_sale_price = COALESCE(device_sale_price, sale_price, 0),
                    table_sale_price  = COALESCE(table_sale_price, sale_price, 0)
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('catalog_items')) {
            Schema::table('catalog_items', function (Blueprint $table): void {
                $columnsToDrop = array_filter([
                    Schema::hasColumn('catalog_items', 'direct_sale_price') ? 'direct_sale_price' : null,
                    Schema::hasColumn('catalog_items', 'device_sale_price') ? 'device_sale_price' : null,
                    Schema::hasColumn('catalog_items', 'table_sale_price') ? 'table_sale_price' : null,
                ]);

                if (! empty($columnsToDrop)) {
                    $table->dropColumn(array_values($columnsToDrop));
                }
            });
        }
    }
};
