<?php

namespace App\Ai\Tools;

use App\Models\Tenant\Shift;
use App\Support\ArabicSearch;
use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetPaymentMethodsSummaryTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'حساب المبالغ المحصلة حسب طريقة الدفع مثل الكاش أو البطاقة/الفيزا أو رصيد العميل، للوردية الحالية أو وردية وفترة محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $shiftNo = $request['shift_no'] ?? null;
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;
        $requestedMethod = $this->canonicalMethod($request['payment_method'] ?? null);

        $query = DB::table('invoice_payments')
            ->join('invoices', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->where('invoices.status', 'posted')
            ->select('invoice_payments.payment_method', DB::raw('SUM(invoice_payments.amount) AS total_amount'), DB::raw('COUNT(invoice_payments.id) AS payments_count'));

        $scope = '';
        if ($shiftNo) {
            $shift = Shift::query()->where('shift_no', $shiftNo)->first();
            if (! $shift) {
                return json_encode(['status' => 'shift_not_found', 'shift_no' => (int) $shiftNo], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            }

            $query->where('invoices.shift_id', $shift->id);
            $scope = 'الوردية رقم '.(int) $shiftNo;
        } elseif ($startDate || $endDate) {
            $clock = app(TenantClock::class);
            $start = $clock->localDateStartUtc($startDate ?? $clock->nowLocal());
            $end = $clock->localDateEndUtc($endDate ?? $clock->nowLocal());
            $query->whereBetween('invoices.issued_at', [$start, $end]);
            $scope = "الفترة من {$startDate} إلى {$endDate}";
        } else {
            $shift = Shift::query()->where('status', 'open')->latest('id')->first();
            if ($shift) {
                $query->where('invoices.shift_id', $shift->id);
                $scope = 'الوردية المفتوحة رقم '.(int) $shift->shift_no;
            } else {
                $clock = app(TenantClock::class);
                [$start, $end] = $clock->localDateRangeUtc($clock->nowLocal());
                $query->whereBetween('invoices.issued_at', [$start, $end]);
                $scope = 'اليوم الحالي';
            }
        }

        if ($requestedMethod !== null) {
            $query->where('invoice_payments.payment_method', $requestedMethod);
        }

        $payments = $query->groupBy('invoice_payments.payment_method')->orderBy('invoice_payments.payment_method')->get();

        return json_encode([
            'status' => $payments->isEmpty() ? 'no_payments_in_scope' : 'ok',
            'scope' => $scope,
            'requested_method' => $requestedMethod,
            'payments' => $payments->map(fn (object $payment): array => [
                'method' => $payment->payment_method,
                'method_label' => $this->methodLabel($payment->payment_method),
                'amount' => round((float) $payment->total_amount, 2),
                'payments_count' => (int) $payment->payments_count,
            ])->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'payment_method' => $schema->string()->description('طريقة الدفع المطلوبة مثل فيزا، بطاقة، كاش، محفظة، أو رصيد عميل.'),
            'shift_no' => $schema->integer()->description('رقم الوردية، واتركه فارغاً لاستخدام الوردية الحالية.'),
            'start_date' => $schema->string()->description('تاريخ البداية YYYY-MM-DD.'),
            'end_date' => $schema->string()->description('تاريخ النهاية YYYY-MM-DD.'),
        ];
    }

    private function canonicalMethod(?string $method): ?string
    {
        $normalized = ArabicSearch::normalize($method);
        if ($normalized === '') {
            return null;
        }

        return match (true) {
            str_contains($normalized, 'فيزا'), str_contains($normalized, 'بطاق'), str_contains($normalized, 'كارت') => 'card',
            str_contains($normalized, 'كاش'), str_contains($normalized, 'نقد') => 'cash',
            str_contains($normalized, 'محفظ') => 'wallet',
            str_contains($normalized, 'رصيد') => 'customer_balance',
            default => $method,
        };
    }

    private function methodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'كاش',
            'card' => 'بطاقة/فيزا',
            'wallet' => 'محفظة',
            'customer_balance' => 'رصيد العميل',
            default => $method,
        };
    }
}
