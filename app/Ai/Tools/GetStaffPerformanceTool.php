<?php

namespace App\Ai\Tools;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetStaffPerformanceTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'تحليل أداء الموظفين والكاشيرات، ومعرفة إجمالي الفواتير والمبيعات وإيرادات اللعب والكافيه التي حققها كل موظف خلال فترة محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDateStr = $request['start_date'] ?? null;
        $endDateStr = $request['end_date'] ?? null;

        $query = DB::table('invoices')
            ->join('users', 'invoices.created_by_user_id', '=', 'users.id')
            ->select(
                'users.name AS staff_name',
                DB::raw('COUNT(invoices.id) AS total_invoices_count'),
                DB::raw('SUM(invoices.play_amount) AS total_play_revenue'),
                DB::raw('SUM(invoices.order_amount) AS total_order_revenue'),
                DB::raw('SUM(invoices.final_amount) AS total_net_revenue')
            )
            ->where('invoices.status', 'posted');

        if ($startDateStr || $endDateStr) {
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->endOfDay();
            $query->whereBetween('invoices.issued_at', [$start, $end]);
        }

        $results = $query->groupBy('users.id', 'users.name')
            ->orderBy('total_net_revenue', 'desc')
            ->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'staff_name' => $row->staff_name,
                'total_invoices_issued' => (int) $row->total_invoices_count,
                'play_revenue_generated' => round((float) $row->total_play_revenue, 2),
                'cafe_sales_generated' => round((float) $row->total_order_revenue, 2),
                'total_net_revenue' => round((float) $row->total_net_revenue, 2),
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('تاريخ بدء فترة التحليل YYYY-MM-DD'),
            'end_date' => $schema->string()
                ->description('تاريخ نهاية فترة التحليل YYYY-MM-DD'),
        ];
    }
}
