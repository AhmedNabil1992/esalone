<?php

namespace App\Ai\Tools;

use App\Models\Tenant\CatalogItem;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCatalogInventoryTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على قائمة بمنتجات الكافيه المتوفرة للبيع، أسعارها، والكميات المتاحة في المخازن.';
    }

    public function handle(Request $request): Stringable|string
    {
        $searchName = $request['search_name'] ?? null;

        $query = CatalogItem::query()
            ->leftJoin('warehouse_stocks', 'catalog_items.id', '=', 'warehouse_stocks.catalog_item_id')
            ->leftJoin('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->select(
                'catalog_items.id',
                'catalog_items.name AS product_name',
                'catalog_items.sale_price',
                'catalog_items.purchase_price',
                DB::raw('SUM(warehouse_stocks.quantity) AS stock_quantity'),
                'warehouses.name AS warehouse_name'
            )
            ->where('catalog_items.is_product', 1)
            ->where('catalog_items.is_active', 1);

        if ($searchName) {
            $query->where('catalog_items.name', 'like', '%'.$searchName.'%');
        }

        $results = $query->groupBy(
            'catalog_items.id',
            'catalog_items.name',
            'catalog_items.sale_price',
            'catalog_items.purchase_price',
            'warehouses.name'
        )->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'product_id' => $row->id,
                'product_name' => $row->product_name,
                'sale_price' => (float) $row->sale_price,
                'cost_price' => (float) $row->purchase_price,
                'stock_quantity' => $row->stock_quantity !== null ? (float) $row->stock_quantity : 0.0,
                'warehouse_name' => $row->warehouse_name ?? 'المخزن الرئيسي',
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search_name' => $schema->string()
                ->description('اسم منتج معين للبحث عنه في الكود (مثال: شاي، قهوة) - اختياري.'),
        ];
    }
}
