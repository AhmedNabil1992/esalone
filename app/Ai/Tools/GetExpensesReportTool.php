<?php

namespace App\Ai\Tools;

use App\Models\Tenant\ExpenseHistory;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetExpensesReportTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على تقرير المصروفات وتفاصيل المبالغ الخارجة من الخزينة مصنفة حسب بنود المصروفات.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDateStr = $request['start_date'] ?? null;
        $endDateStr = $request['end_date'] ?? null;

        $query = ExpenseHistory::query()
            ->leftJoin('expenses_types', 'expense_histories.expenses_id', '=', 'expenses_types.id')
            ->select(
                'expense_histories.id',
                'expenses_types.name AS expense_type',
                'expense_histories.amount',
                'expense_histories.description',
                'expense_histories.occurred_at'
            );

        if ($startDateStr || $endDateStr) {
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->endOfDay();
            $query->whereBetween('expense_histories.occurred_at', [$start, $end]);
        }

        $results = $query->orderBy('expense_histories.occurred_at', 'desc')->get();

        $output = [];
        $totalExpenses = 0.0;

        foreach ($results as $row) {
            $amount = (float) $row->amount;
            $totalExpenses += $amount;
            $output['items'][] = [
                'expense_id' => $row->id,
                'category' => $row->expense_type ?? 'مصروفات عامة',
                'amount' => $amount,
                'notes' => $row->description,
                'date' => Carbon::parse($row->occurred_at)->toDateTimeString(),
            ];
        }

        $output['summary'] = [
            'total_expenses' => round($totalExpenses, 2),
            'records_count' => count($results),
            'period' => ($startDateStr || $endDateStr) ? "من {$startDateStr} إلى {$endDateStr}" : 'آخر 30 يوماً',
        ];

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('تاريخ البداية لفلترة المصروفات YYYY-MM-DD'),
            'end_date' => $schema->string()
                ->description('تاريخ النهاية لفلترة المصروفات YYYY-MM-DD'),
        ];
    }
}
