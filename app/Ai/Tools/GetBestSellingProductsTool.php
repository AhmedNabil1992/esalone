<?php

namespace App\Ai\Tools;

use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetBestSellingProductsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'تحليل مبيعات الكافيه وتحديد المنتجات الأكثر طلباً ومبيعاً ومعدل تكرارها وإيراداتها خلال فترة محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDateStr = $request['start_date'] ?? null;
        $endDateStr = $request['end_date'] ?? null;

        $query = DB::table('order_session_details')
            ->join('catalog_items', 'order_session_details.catalog_item_id', '=', 'catalog_items.id')
            ->select(
                'catalog_items.name AS product_name',
                DB::raw('SUM(order_session_details.quantity) AS total_quantity'),
                DB::raw('SUM(order_session_details.total) AS total_sales_revenue'),
                DB::raw('AVG(order_session_details.price) AS average_price')
            )
            ->where('order_session_details.status', '!=', 'cancelled');

        if ($startDateStr || $endDateStr) {
            $clock = app(TenantClock::class);
            $start = $clock->localDateStartUtc($startDateStr ?? $clock->nowLocal()->subDays(30));
            $end = $clock->localDateEndUtc($endDateStr ?? $clock->nowLocal());
            $query->whereBetween('order_session_details.created_at', [$start, $end]);
        }

        $results = $query->groupBy('catalog_items.id', 'catalog_items.name')
            ->orderBy('total_quantity', 'desc')
            ->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'product_name' => $row->product_name,
                'total_quantity_sold' => (int) $row->total_quantity,
                'total_revenue' => round((float) $row->total_sales_revenue, 2),
                'average_sale_price' => round((float) $row->average_price, 2),
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('تاريخ بدء التحليل YYYY-MM-DD'),
            'end_date' => $schema->string()
                ->description('تاريخ نهاية التحليل YYYY-MM-DD'),
        ];
    }
}
