<?php

namespace App\Ai\Tools;

use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Shift;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetRevenueReportTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على تقارير الإيرادات والمبيعات والأرباح والمصروفات بالتفصيل لفترة زمنية محددة أو لشيفت (وردية) معينة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDateStr = $request['start_date'] ?? null;
        $endDateStr = $request['end_date'] ?? null;
        $shiftId = $request['shift_id'] ?? null;

        $invoiceQuery = Invoice::query()->where('status', 'posted');
        $expenseQuery = ExpenseHistory::query();

        $reportTitle = '';

        if ($shiftId) {
            $invoiceQuery->where('shift_id', $shiftId);
            $expenseQuery->where('shift_id', $shiftId);
            $shift = Shift::find($shiftId);
            $reportTitle = 'الوردية رقم: '.($shift ? $shift->shift_no : $shiftId);
        } elseif ($startDateStr || $endDateStr) {
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::today()->startOfDay();
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::today()->endOfDay();

            $invoiceQuery->whereBetween('issued_at', [$start, $end]);
            $expenseQuery->whereBetween('occurred_at', [$start, $end]);
            $reportTitle = "الفترة من {$start->toDateString()} إلى {$end->toDateString()}";
        } else {
            // Default to today's open shift if exists, otherwise today's date
            $activeShift = Shift::where('status', 'open')->first();
            if ($activeShift) {
                $invoiceQuery->where('shift_id', $activeShift->id);
                $expenseQuery->where('shift_id', $activeShift->id);
                $reportTitle = "الوردية المفتوحة حالياً (رقم {$activeShift->shift_no})";
            } else {
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $invoiceQuery->whereBetween('issued_at', [$start, $end]);
                $expenseQuery->whereBetween('occurred_at', [$start, $end]);
                $reportTitle = "اليوم الحالي ({$start->toDateString()})";
            }
        }

        $invoices = $invoiceQuery->get();
        $expenses = $expenseQuery->get();

        $playAmount = (float) $invoices->sum('play_amount');
        $orderAmount = (float) $invoices->sum('order_amount');
        $subtotal = (float) $invoices->sum('subtotal');
        $discountAmount = (float) $invoices->sum('discount_amount');
        $finalAmount = (float) $invoices->sum('final_amount');
        $paidAmount = (float) $invoices->sum('paid_amount');

        $totalExpenses = (float) $expenses->sum('amount');
        $netProfit = $finalAmount - $totalExpenses;

        $output = [
            'report_scope' => $reportTitle,
            'play_revenue' => round($playAmount, 2),
            'cafe_sales' => round($orderAmount, 2),
            'subtotal' => round($subtotal, 2),
            'discounts_given' => round($discountAmount, 2),
            'total_invoiced_revenue' => round($finalAmount, 2),
            'total_payments_collected' => round($paidAmount, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'invoices_count' => $invoices->count(),
            'expenses_count' => $expenses->count(),
        ];

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('تاريخ بدء التقرير بتنسيق YYYY-MM-DD (مثال: 2026-07-10)')
                ->optional(),
            'end_date' => $schema->string()
                ->description('تاريخ نهاية التقرير بتنسيق YYYY-MM-DD (مثال: 2026-07-15)')
                ->optional(),
            'shift_id' => $schema->integer()
                ->description('معرف الوردية لعمل تقرير عن وردية محددة')
                ->optional(),
        ];
    }
}
