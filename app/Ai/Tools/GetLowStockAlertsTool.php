<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetLowStockAlertsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على تقرير بالنواقص والمنتجات التي قارب مخزونها على النفاد مقارنة بالحد الأدنى للطلب.';
    }

    public function handle(Request $request): Stringable|string
    {
        // Query low stock items (current stock <= min_quantity)
        $results = DB::table('catalog_items')
            ->leftJoin('warehouse_stocks', 'catalog_items.id', '=', 'warehouse_stocks.catalog_item_id')
            ->select(
                'catalog_items.id',
                'catalog_items.name AS product_name',
                'catalog_items.sale_price',
                'catalog_items.min_quantity',
                DB::raw('COALESCE(SUM(warehouse_stocks.quantity), 0) AS current_stock')
            )
            ->where('catalog_items.is_product', 1)
            ->where('catalog_items.is_active', 1)
            ->groupBy('catalog_items.id', 'catalog_items.name', 'catalog_items.sale_price', 'catalog_items.min_quantity')
            ->get();

        $output = [];
        foreach ($results as $row) {
            $minQty = (int) $row->min_quantity;
            $currentStock = (float) $row->current_stock;

            // Only report if stock is less than or equal to minimum limit (and min_quantity > 0)
            if ($minQty > 0 && $currentStock <= $minQty) {
                $output[] = [
                    'product_name' => $row->product_name,
                    'current_stock' => $currentStock,
                    'minimum_required_stock' => $minQty,
                    'shortage_quantity' => $minQty - $currentStock,
                    'sale_price' => (float) $row->sale_price,
                    'status' => $currentStock == 0 ? 'منفد تماماً' : 'منخفض جداً',
                ];
            }
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return []; // No arguments required
    }
}
