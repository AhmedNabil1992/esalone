<!doctype html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تقرير تقفيل الشيفت رقم {{ $shift->shift_no }}</title>
    <style>
        @page { size: 75mm auto; margin: 0; }
        * { box-sizing: border-box; }
        html, body { width: 100%; max-width: 75mm; margin: 0; padding: 0; overflow-x: hidden; }
        body { color: #000; background: #fff; font: 11px/1.4 "DejaVu Sans", Cairo, Arial, sans-serif; direction: rtl; }
        .receipt { width: 100%; max-width: 75mm; padding: 2mm; overflow: hidden; }
        .center, .closing th, .closing td { text-align: center; }
        .logo { display: block; max-width: 54mm; max-height: 20mm; margin: 0 auto 4px; object-fit: contain; }
        h1 { margin: 0; font-size: 17px; } h2 { margin: 2px 0 0; font-size: 13px; }
        .rule { margin: 6px 0; border-top: 1px dashed #000; }
        .section-title { margin: 6px 0 3px; font-size: 12px; font-weight: 800; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 1mm; vertical-align: top; } th { width: 42%; text-align: right; font-weight: 600; }
        td.amount { direction: ltr; text-align: left; padding-left: 2mm; white-space: normal; overflow-wrap: anywhere; }
        .money-list { border-top: 1px solid #000; }
        .money-list tr { border-bottom: 1px dotted #777; }
        .money-list th, .money-list td { padding-top: 3px; padding-bottom: 3px; }
        .money-list td { direction: ltr; text-align: left; padding-left: 2mm; font-weight: 700; white-space: nowrap; }
        .summary tr.total th, .summary tr.total td { padding-top: 4px; border-top: 1px solid #000; font-size: 12px; font-weight: 800; }
        .closing { margin-top: 6px; table-layout: fixed; border: 1px solid #000; }
        .closing th, .closing td { width: 25%; padding: 4px 1px; border: 1px solid #000; overflow-wrap: anywhere; }
        .closing th { font-size: 8px; line-height: 1.3; } .closing td { direction: ltr; font-size: 10px; font-weight: 800; }
        .notes { margin-top: 6px; white-space: pre-wrap; }
        .footer { margin-top: 8px; text-align: center; font-size: 9px; }
        .screen-actions { display: flex; width: 100%; max-width: 75mm; margin: 12px 0; padding: 0 2mm; gap: 8px; }
        .screen-actions button, .screen-actions a { flex: 1; padding: 9px; border: 0; border-radius: 6px; color: #fff; background: #2563eb; font: 700 12px Cairo, Arial, sans-serif; text-align: center; text-decoration: none; cursor: pointer; }
        .screen-actions a { background: #475569; }
        @media screen { html, body { margin: 0 auto; } }
        @media print {
            html, body, .receipt { width: 100%; max-width: 75mm; margin: 0; }
            .screen-actions { display: none; }
        }
    </style>
</head>
<body dir="rtl">
@php
    $logoUrl = $logoPath;
    if (filled($logoPath) && ! str_starts_with($logoPath, 'data:') && ! str_starts_with($logoPath, 'http://') && ! str_starts_with($logoPath, 'https://')) {
        $logoUrl = tenant_asset($logoPath);
    }
    $incomeTotal = (float) $shift->playstation_amount + (float) $shift->sales_amount + (float) $shift->customer_add_amount + (float) $shift->income_history_amount + (float) $shift->tax_amount + (float) $shift->service_amount;
    $deductionTotal = (float) $shift->purchase_amount + (float) $shift->expenses_amount + (float) $shift->discount_amount + (float) $shift->customer_credit_amount + (float) $shift->customer_minus_amount + (float) $shift->credit_amount;
    $clock = app(\App\Support\Tenant\TenantClock::class);
@endphp
<main class="receipt">
    @if(filled($logoUrl))<img class="logo" src="{{ $logoUrl }}" alt="{{ $shopName }}">@endif
    <header class="center"><h1>{{ $shopName }}</h1><h2>تقرير تقفيل الشيفت</h2></header>
    <div class="rule"></div>
    <table>
        <tr><th>رقم الشيفت</th><td class="amount">{{ $shift->shift_no }}</td></tr>
        <tr><th>الفرع</th><td class="amount">{{ $shift->branch?->name ?? '-' }}</td></tr>
        <tr><th>وقت البداية</th><td class="amount">{{ $shift->shift_from ? $clock->utcToLocal($shift->shift_from)->format('Y-m-d h:i A') : '-' }}</td></tr>
        <tr><th>فتح بواسطة</th><td class="amount">{{ $shift->opener?->name ?? '-' }}</td></tr>
        <tr><th>وقت النهاية</th><td class="amount">{{ $shift->shift_to ? $clock->utcToLocal($shift->shift_to)->format('Y-m-d h:i A') : '-' }}</td></tr>
        <tr><th>غلق بواسطة</th><td class="amount">{{ $shift->closer?->name ?? '-' }}</td></tr>
        <tr><th>رصيد البداية</th><td class="amount">{{ number_format((float) $shift->start_amount, 2) }}</td></tr>
    </table>
    <div class="rule"></div>
    <div class="section-title">الإيرادات</div>
    <table class="money-list"><tbody>
        <tr><th>إيراد اللعب</th><td>{{ number_format((float) $shift->playstation_amount, 2) }}</td></tr>
        <tr><th>المبيعات</th><td>{{ number_format((float) $shift->sales_amount, 2) }}</td></tr>
        <tr><th>إضافة رصيد عملاء</th><td>{{ number_format((float) $shift->customer_add_amount, 2) }}</td></tr>
        <tr><th>إيرادات أخرى</th><td>{{ number_format((float) $shift->income_history_amount, 2) }}</td></tr>
        <tr><th>الضريبة</th><td>{{ number_format((float) $shift->tax_amount, 2) }}</td></tr>
        <tr><th>الخدمة</th><td>{{ number_format((float) $shift->service_amount, 2) }}</td></tr>
    </tbody></table>
    <table class="summary"><tr class="total"><th>إجمالي الإيرادات</th><td class="amount">{{ number_format($incomeTotal, 2) }}</td></tr></table>
    <div class="section-title">المستقطع من الإيراد</div>
    <table class="money-list"><tbody>
        <tr><th>المشتريات</th><td>{{ number_format((float) $shift->purchase_amount, 2) }}</td></tr>
        <tr><th>المصروفات</th><td>{{ number_format((float) $shift->expenses_amount, 2) }}</td></tr>
        <tr><th>الخصومات</th><td>{{ number_format((float) $shift->discount_amount, 2) }}</td></tr>
        <tr><th>سحب رصيد عملاء</th><td>{{ number_format((float) $shift->customer_minus_amount, 2) }}</td></tr>
        <tr><th>مديونية العملاء</th><td>{{ number_format((float) $shift->credit_amount, 2) }}</td></tr>
        <tr><th>الدفع برصيد العميل</th><td>{{ number_format((float) $shift->customer_credit_amount, 2) }}</td></tr>
    </tbody></table>
    <table class="summary"><tr><th>إجمالي الاستقطاعات</th><td class="amount">{{ number_format($deductionTotal, 2) }}</td></tr><tr class="total"><th>صافي حركة الشيفت</th><td class="amount">{{ number_format($incomeTotal - $deductionTotal, 2) }}</td></tr></table>
    <div class="section-title">التقفيل</div>
    <table class="closing"><thead><tr><th>إجمالي الشيفت</th><th>المتبقي المتوقع</th><th>الفعلي</th><th>الفرق</th></tr></thead><tbody><tr>
        <td>{{ number_format($incomeTotal - $deductionTotal, 2) }}</td><td>{{ number_format((float) $shift->expected_amount, 2) }}</td><td>{{ number_format((float) $shift->actual_amount, 2) }}</td><td>{{ number_format((float) $shift->difference_amount, 2) }}</td>
    </tr></tbody></table>
    @if($cashBoxes->isNotEmpty())
        <div class="section-title">أرصدة الخزائن الفعلية</div><table>@foreach($cashBoxes as $cashBox)<tr><th>{{ $cashBox['name'] }}</th><td class="amount">{{ number_format((float) $cashBox['actual_amount'], 2) }}</td></tr>@endforeach</table>
    @endif
    @if(filled($shift->notes))<div class="notes"><strong>ملاحظات:</strong> {{ $shift->notes }}</div>@endif
    {{-- @if(filled($footerMessage))<div class="footer">{{ $footerMessage }}</div>@endif --}}
    <div class="footer">{{ $clock->nowLocal()->format('Y-m-d h:i:s A') }}</div>
</main>
<div class="screen-actions">
    <button id="print-shift-report" type="button">طباعة التقرير</button>
    <a href="{{ $returnUrl }}">الرجوع لسجل الشيفتات</a>
</div>
<script>
    (() => {
        const bridgeUrl = @js($bridgeUrl);
        const printerName = @js($printerName);
        const autoPrint = @js($autoPrint);
        const returnUrl = @js($returnUrl);
        let printing = false;

        const browserPrint = () => {
            window.print();
        };

        const printReport = () => {
            if (printing) {
                return;
            }

            if (!bridgeUrl || !printerName) {
                browserPrint();
                return;
            }

            printing = true;
            let socket;
            let finished = false;
            let timeoutId;

            const fallback = (reason) => {
                if (finished) {
                    return;
                }

                finished = true;
                printing = false;
                clearTimeout(timeoutId);
                try { socket?.close(); } catch (error) {}
                console.warn('Bridge shift report printing failed (' + reason + '). Falling back to browser printing.');
                browserPrint();
            };

            timeoutId = setTimeout(() => fallback('timeout'), 5000);

            try {
                socket = new WebSocket(bridgeUrl);
                socket.onopen = () => {
                    const printableHtml = document.documentElement.outerHTML
                        .replace(/<div class="screen-actions"[\s\S]*?<\/div>/i, '')
                        .replace(/<script[\s\S]*?<\/script>/gi, '');
                    socket.send(`PRINT|${printerName}|${printableHtml}`);
                };
                socket.onerror = () => fallback('connection_error');
                socket.onmessage = (event) => {
                    try {
                        const response = JSON.parse(event.data);
                        if (response.status !== 'success' && response.status !== 'printed') {
                            fallback(response.message || 'print_error');
                            return;
                        }

                        finished = true;
                        printing = false;
                        clearTimeout(timeoutId);
                        try { socket.close(); } catch (error) {}

                        if (autoPrint) {
                            window.location.replace(returnUrl);
                        }
                    } catch (error) {
                        fallback('invalid_response');
                    }
                };
            } catch (error) {
                fallback('websocket_error');
            }
        };

        document.getElementById('print-shift-report').addEventListener('click', printReport);

        if (autoPrint) {
            window.addEventListener('load', printReport);
            window.addEventListener('afterprint', () => window.location.replace(returnUrl));
        }
    })();
</script>
</body>
</html>
