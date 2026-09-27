<?php

namespace App\Ai\Tools;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetShiftSummaryTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'جلب ملخص مالي وتشغيلي كامل وتفصيلي للوردية الحالية المفتوحة أو لوردية سابقة محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $shiftNo = $request['shift_no'] ?? null;
        $branchId = $this->activeBranchId();
        $shifts = \DB::table('shifts')
            ->when($branchId, fn (Builder $query, int $id): Builder => $query->where('branch_id', $id));

        if ($shiftNo) {
            $shift = (clone $shifts)->where('shift_no', $shiftNo)->first();
        } else {
            // Find current open shift or the latest closed shift
            $shift = (clone $shifts)->where('status', 'open')->orderByDesc('id')->first()
                ?? (clone $shifts)->orderByDesc('id')->first();
        }

        if (! $shift) {
            return json_encode([
                'status' => 'shift_not_found',
                'shift_number' => $shiftNo,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $isClosed = ($shift->status !== 'open');
        $shiftId = $shift->id;

        // If it is open, calculate totals dynamically from live database tables
        if (! $isClosed) {
            $invoices = \DB::table('invoices')
                ->where('shift_id', $shiftId)
                ->where('status', 'posted')
                ->get();

            $expenses = \DB::table('expense_histories')
                ->where('shift_id', $shiftId)
                ->get();

            $playAmount = (float) $invoices->sum('play_amount');
            $salesAmount = (float) $invoices->sum('order_amount');
            $discountAmount = (float) $invoices->sum('discount_amount');
            $expensesAmount = (float) $expenses->sum('amount');

            $startAmount = (float) $shift->start_amount;
            $invoicedRevenue = (float) $invoices->sum('final_amount');
            $expectedAmount = $startAmount + $invoicedRevenue - $expensesAmount;
            $actualAmount = null; // Shift is still open
        } else {
            // Shift is closed, read pre-calculated totals
            $playAmount = (float) $shift->playstation_amount;
            $salesAmount = (float) $shift->sales_amount;
            $discountAmount = (float) $shift->discount_amount;
            $expensesAmount = (float) $shift->expenses_amount;
            $startAmount = (float) $shift->start_amount;
            $expectedAmount = (float) $shift->expected_amount;
            $actualAmount = (float) $shift->actual_amount;
        }

        $openingUser = \DB::table('users')->where('id', $shift->opened_by_user_id)->value('name') ?? 'مستخدم النظام';
        $closingUser = $shift->closed_by_user_id
            ? (\DB::table('users')->where('id', $shift->closed_by_user_id)->value('name') ?? 'مستخدم النظام')
            : null;

        $output = [
            'shift_id' => $shift->id,
            'shift_number' => (int) $shift->shift_no,
            'shift_date' => $shift->shift_date,
            'status' => $shift->status === 'open' ? 'مفتوحة حالياً' : 'مغلقة',
            'opened_at' => Carbon::parse($shift->shift_from)->toDateTimeString(),
            'closed_at' => $shift->shift_to ? Carbon::parse($shift->shift_to)->toDateTimeString() : 'مستمرة حتى الآن',
            'opened_by' => $openingUser,
            'closed_by' => $closingUser,
            'financials' => [
                'start_cash_amount' => $startAmount,
                'playstation_revenue' => round($playAmount, 2),
                'cafe_sales_revenue' => round($salesAmount, 2),
                'total_discounts' => round($discountAmount, 2),
                'total_expenses' => round($expensesAmount, 2),
                'expected_cash_in_box' => round($expectedAmount, 2),
                'actual_cash_in_box' => $actualAmount !== null ? round($actualAmount, 2) : 'الوردية لم تغلق بعد ولن يتم مطابقة العجز والزيادة',
                'balance_variance' => $actualAmount !== null ? round($actualAmount - $expectedAmount, 2) : 0.0,
            ],
            'notes' => $shift->notes,
        ];

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'shift_no' => $schema->integer()
                ->description('رقم الوردية المطلوب جلب ملخصها (مثال: 5) - اختياري، عند تركه فارغاً يجلب الوردية الحالية.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
