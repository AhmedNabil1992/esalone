<?php

namespace App\Ai\Tools;

use App\Models\Tenant\CatalogItem;
use App\Support\ArabicSearch;
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
        $searchName = trim((string) ($request['search_name'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);

        $catalogItems = CatalogItem::query()
            ->get(['id', 'name', 'is_product', 'is_component', 'is_active']);

        if ($searchName !== '') {
            $matchedIds = $catalogItems
                ->filter(fn (CatalogItem $item): bool => ArabicSearch::matches($item->name, $searchName))
                ->pluck('id');

            if ($matchedIds->isEmpty()) {
                return json_encode([
                    'status' => 'catalog_item_not_matched',
                    'searched_for' => $searchName,
                    'suggestions' => ArabicSearch::suggestions($catalogItems->pluck('name'), $searchName),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }
        }

        $query = DB::table('catalog_items')
            ->leftJoin('warehouse_stocks', 'catalog_items.id', '=', 'warehouse_stocks.catalog_item_id')
            ->leftJoin('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->select(
                'catalog_items.id',
                'catalog_items.name AS product_name',
                'catalog_items.sale_price',
                'catalog_items.purchase_price',
                'catalog_items.is_product',
                'catalog_items.is_component',
                'catalog_items.is_active',
                DB::raw('SUM(warehouse_stocks.quantity) AS stock_quantity'),
                'warehouses.name AS warehouse_name'
            );

        if ($searchName !== '') {
            $query->whereIn('catalog_items.id', $matchedIds);
        } else {
            $query->where('catalog_items.is_product', 1)
                ->where('catalog_items.is_active', 1);
        }

        $results = $query->groupBy(
            'catalog_items.id',
            'catalog_items.name',
            'catalog_items.sale_price',
            'catalog_items.purchase_price',
            'catalog_items.is_product',
            'catalog_items.is_component',
            'catalog_items.is_active',
            'warehouses.name'
        )->limit($limit + 1)->get();

        $truncated = $results->count() > $limit;
        $results = $results->take($limit);

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'product_id' => $row->id,
                'product_name' => $row->product_name,
                'sale_price' => (float) $row->sale_price,
                'cost_price' => (float) $row->purchase_price,
                'stock_quantity' => $row->stock_quantity !== null ? (float) $row->stock_quantity : 0.0,
                'warehouse_name' => $row->warehouse_name ?? 'المخزن الرئيسي',
                'is_active' => (bool) $row->is_active,
                'item_type' => match (true) {
                    (bool) $row->is_product => 'product',
                    (bool) $row->is_component => 'component',
                    default => 'catalog_item',
                },
            ];
        }

        return json_encode([
            'status' => match (true) {
                $output === [] => 'no_active_products',
                collect($output)->contains(fn (array $item): bool => ! $item['is_active']) => 'matched_items_include_inactive',
                default => 'ok',
            },
            'searched_for' => $searchName !== '' ? $searchName : null,
            'result_count' => count($output),
            'truncated' => $truncated,
            'products' => $output,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search_name' => $schema->string()
                ->description('اسم منتج معين للبحث عنه في الكود (مثال: شاي، قهوة) - اختياري.'),
            'limit' => $schema->integer()
                ->description('أقصى عدد نتائج بين 1 و50، والافتراضي 25.'),
        ];
    }
}
