<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetLowStockAlertsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Return active products whose stock in the selected branch warehouses is at or below each item minimum quantity.';
    }

    public function handle(Request $request): Stringable|string
    {
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);
        $branchId = $this->activeBranchId();

        $stockByItem = DB::table('warehouse_stocks')
            ->join('warehouses', 'warehouse_stocks.warehouse_id', '=', 'warehouses.id')
            ->when($branchId, fn (Builder $query, int $id): Builder => $query->where('warehouses.branch_id', $id))
            ->where('warehouses.is_active', true)
            ->select('warehouse_stocks.catalog_item_id', DB::raw('SUM(warehouse_stocks.quantity) AS current_stock'))
            ->groupBy('warehouse_stocks.catalog_item_id');

        $rows = DB::table('catalog_items')
            ->leftJoinSub($stockByItem, 'stock_totals', 'catalog_items.id', '=', 'stock_totals.catalog_item_id')
            ->select(
                'catalog_items.id',
                'catalog_items.name',
                'catalog_items.sale_price',
                'catalog_items.min_quantity',
                DB::raw('COALESCE(stock_totals.current_stock, 0) AS current_stock')
            )
            ->where('catalog_items.is_product', true)
            ->where('catalog_items.is_active', true)
            ->where('catalog_items.min_quantity', '>', 0)
            ->whereRaw('COALESCE(stock_totals.current_stock, 0) <= catalog_items.min_quantity')
            ->orderByRaw('(catalog_items.min_quantity - COALESCE(stock_totals.current_stock, 0)) DESC')
            ->limit($limit)
            ->get();

        return json_encode([
            'status' => $rows->isEmpty() ? 'no_low_stock_items' : 'low_stock_items_found',
            'branch_id' => $branchId,
            'items' => $rows->map(static function (object $row): array {
                $minimum = (float) $row->min_quantity;
                $current = (float) $row->current_stock;

                return [
                    'item_id' => (int) $row->id,
                    'product_name' => (string) $row->name,
                    'current_stock' => $current,
                    'minimum_required_stock' => $minimum,
                    'shortage_quantity' => max(0, $minimum - $current),
                    'sale_price' => (float) $row->sale_price,
                    'stock_status' => $current <= 0 ? 'out_of_stock' : 'low_stock',
                ];
            })->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->description('Maximum rows to return. Defaults to 25 and never exceeds 50.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
