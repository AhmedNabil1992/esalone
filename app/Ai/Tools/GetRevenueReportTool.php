<?php

namespace App\Ai\Tools;

use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Shift;
use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetRevenueReportTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Return invoiced revenue, collected payments, expenses, and net result for a local date range or a shift ID/visible shift number.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDate = trim((string) ($request['start_date'] ?? ''));
        $endDate = trim((string) ($request['end_date'] ?? ''));
        $shiftId = isset($request['shift_id']) ? (int) $request['shift_id'] : null;
        $shiftNo = isset($request['shift_no']) ? (int) $request['shift_no'] : null;
        $clock = app(TenantClock::class);

        $invoiceQuery = Invoice::query()->where('status', 'posted');
        $expenseQuery = ExpenseHistory::query();
        $reportScope = '';

        if ($shiftNo !== null && $shiftId === null) {
            $shift = Shift::query()->where('shift_no', $shiftNo)->orderByDesc('id')->first();

            if (! $shift) {
                return json_encode([
                    'status' => 'shift_not_found',
                    'shift_number' => $shiftNo,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }

            $shiftId = $shift->id;
        }

        if ($shiftId !== null) {
            $shift = Shift::find($shiftId);
            if (! $shift) {
                return json_encode([
                    'status' => 'shift_not_found',
                    'shift_id' => $shiftId,
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }

            $invoiceQuery->where('shift_id', $shift->id);
            $expenseQuery->where('shift_id', $shift->id);
            $reportScope = 'shift';
            $scopeDetails = ['shift_id' => $shift->id, 'shift_number' => (int) $shift->shift_no];
        } elseif ($startDate !== '' || $endDate !== '') {
            $resolvedStartDate = $startDate !== '' ? $startDate : $endDate;
            $resolvedEndDate = $endDate !== '' ? $endDate : $startDate;
            [$startUtc] = $clock->localDateRangeUtc($resolvedStartDate);
            [, $endUtc] = $clock->localDateRangeUtc($resolvedEndDate);
            $invoiceQuery->whereBetween('issued_at', [$startUtc, $endUtc]);
            $expenseQuery->whereBetween('occurred_at', [$startUtc, $endUtc]);
            $reportScope = 'date_range';
            $scopeDetails = ['date_from' => $resolvedStartDate, 'date_to' => $resolvedEndDate];
        } else {
            $activeShift = Shift::query()->where('status', 'open')->orderByDesc('id')->first();

            if ($activeShift) {
                $invoiceQuery->where('shift_id', $activeShift->id);
                $expenseQuery->where('shift_id', $activeShift->id);
                $reportScope = 'current_open_shift';
                $scopeDetails = ['shift_id' => $activeShift->id, 'shift_number' => (int) $activeShift->shift_no];
            } else {
                $today = $clock->nowLocal()->toDateString();
                [$startUtc, $endUtc] = $clock->localDateRangeUtc($today);
                $invoiceQuery->whereBetween('issued_at', [$startUtc, $endUtc]);
                $expenseQuery->whereBetween('occurred_at', [$startUtc, $endUtc]);
                $reportScope = 'today';
                $scopeDetails = ['date' => $today];
            }
        }

        $invoices = $invoiceQuery->get();
        $expenses = $expenseQuery->get();
        $finalAmount = (float) $invoices->sum('final_amount');
        $totalExpenses = (float) $expenses->sum('amount');

        return json_encode([
            'status' => 'revenue_report_ready',
            'report_scope' => $reportScope,
            'scope' => $scopeDetails,
            'play_revenue' => round((float) $invoices->sum('play_amount'), 2),
            'cafe_sales' => round((float) $invoices->sum('order_amount'), 2),
            'subtotal' => round((float) $invoices->sum('subtotal'), 2),
            'discounts_given' => round((float) $invoices->sum('discount_amount'), 2),
            'total_invoiced_revenue' => round($finalAmount, 2),
            'total_payments_collected' => round((float) $invoices->sum('paid_amount'), 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_result' => round($finalAmount - $totalExpenses, 2),
            'invoices_count' => $invoices->count(),
            'expenses_count' => $expenses->count(),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()->description('Optional local start date in YYYY-MM-DD format.'),
            'end_date' => $schema->string()->description('Optional local end date in YYYY-MM-DD format.'),
            'shift_id' => $schema->integer()->description('Database shift ID. Use only when the user explicitly gives an ID.'),
            'shift_no' => $schema->integer()->description('Visible shift number. Prefer this when the user says shift number or وردية رقم.'),
        ];
    }
}
