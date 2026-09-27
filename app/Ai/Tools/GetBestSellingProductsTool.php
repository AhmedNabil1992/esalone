<?php

namespace App\Ai\Tools;

use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetBestSellingProductsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Rank sold cafe products from the persisted market history, including completed and direct sales, for an optional local date range.';
    }

    public function handle(Request $request): Stringable|string
    {
        $clock = app(TenantClock::class);
        $startDate = trim((string) ($request['start_date'] ?? ''));
        $endDate = trim((string) ($request['end_date'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 20), 1), 50);

        if ($startDate === '' && $endDate === '') {
            $endDate = $clock->nowLocal()->toDateString();
            $startDate = $clock->nowLocal()->subDays(29)->toDateString();
        } elseif ($startDate === '') {
            $startDate = $endDate;
        } elseif ($endDate === '') {
            $endDate = $startDate;
        }

        $rows = DB::table('market_history')
            ->leftJoin('catalog_items', 'market_history.catalog_item_id', '=', 'catalog_items.id')
            ->select(
                'market_history.catalog_item_id',
                DB::raw('COALESCE(catalog_items.name, market_history.subject_name) AS product_name'),
                DB::raw('SUM(market_history.quantity) AS total_quantity'),
                DB::raw('SUM(market_history.total_amount) AS total_sales_revenue'),
                DB::raw('AVG(market_history.unit_price) AS average_price')
            )
            ->whereNotNull('market_history.catalog_item_id')
            ->whereBetween('market_history.transaction_date', [$startDate, $endDate])
            ->when($this->activeBranchId(), fn (Builder $query, int $branchId): Builder => $query->where('market_history.branch_id', $branchId))
            ->groupBy('market_history.catalog_item_id', 'catalog_items.name', 'market_history.subject_name')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return json_encode([
            'status' => $rows->isEmpty() ? 'no_product_sales_in_scope' : 'best_selling_products_found',
            'date_from' => $startDate,
            'date_to' => $endDate,
            'products' => $rows->map(static fn (object $row): array => [
                'catalog_item_id' => (int) $row->catalog_item_id,
                'product_name' => (string) $row->product_name,
                'total_quantity_sold' => (float) $row->total_quantity,
                'total_revenue' => round((float) $row->total_sales_revenue, 2),
                'average_sale_price' => round((float) $row->average_price, 2),
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Optional local start date in YYYY-MM-DD format. Defaults to the last 30 days.'),
            'end_date' => $schema->string()->description('Optional local end date in YYYY-MM-DD format. Defaults to the last 30 days.'),
            'limit' => $schema->integer()->description('Maximum products to return. Defaults to 20 and never exceeds 50.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
