<!doctype html>
<html lang="ar" @if(isset($isPdf) && $isPdf) dir="ltr" @else dir="rtl" @endif class="@if(isset($noPrint) && $noPrint) public-html @endif">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>فاتورة رقم {{ $invoice->invoice_no }}</title>
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html {
            width: 80mm;
            max-width: 80mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            margin: 0;
            padding: 0;
            width: 80mm;
            max-width: 80mm;
            font-family: 'DejaVu Sans', 'Cairo', 'Arial', sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.5;
            @if(isset($isPdf) && $isPdf)
            direction: ltr;
            text-align: left;
            @else
            direction: rtl;
            text-align: right;
            @endif
            transform: none !important;
            overflow: hidden;
        }

        .receipt {
            width: 100%;
            margin: 0 auto;
            padding: 2mm 4mm;
        }

        .center {
            text-align: center;
        }

        .logo {
            max-width: 56mm;
            max-height: 22mm;
            object-fit: contain;
            margin: 0 auto 4px;
            display: block;
        }

        .title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .muted {
            color: #000;
            font-size: 11px;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 2px 8px;
            vertical-align: top;
        }

        th {
            font-size: 12px;
            font-weight: 700;
            text-align: right;
        }

        .value {
            text-align: left;
            direction: ltr;
            unicode-bidi: isolate;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 4px;
            @if(isset($isPdf) && $isPdf)
            text-align: right;
            @endif
        }

        .summary td {
            padding: 2px 0;
        }

        .total {
            font-size: 14px;
            font-weight: 700;
        }

        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 11px;
        }

        .small {
            font-size: 10px;
        }

        @media print {
            html,
            body {
                width: 80mm;
                max-width: 80mm;
                overflow: hidden;
            }

            .receipt {
                width: 100%;
                margin: 0 auto;
                padding: 2mm 4mm;
            }
        }

        @media screen {
            html.public-html {
                width: 100%;
                max-width: 100%;
                background-color: #f3f4f6;
                overflow: auto;
                display: flex;
                justify-content: center;
                align-items: flex-start;
                padding: 20px 10px;
                height: 100%;
            }
            html.public-html body {
                background-color: #ffffff;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                border-radius: 12px;
                padding: 20px;
                width: 100%;
                max-width: 420px;
                margin: 0 auto;
                overflow: auto;
                height: auto;
            }
            html.public-html .receipt {
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
@php
    $invoiceNumber = trim((string) $invoice->invoice_no);
    $lastInvoiceSegment = strrchr($invoiceNumber, '-');
    $printedInvoiceNo = $lastInvoiceSegment !== false ? ltrim($lastInvoiceSegment, '-') : $invoiceNumber;
    $discountAmount = (float) $invoice->discount_amount;
    $serviceAmount = (float) $invoice->service_amount;
    $taxAmount = (float) $invoice->tax_amount;

    $isPdfActive = isset($isPdf) && $isPdf;

    $shape = function ($text) use ($isPdfActive) {
        if (empty($text) || !$isPdfActive) {
            return $text;
        }
        if (preg_match('/\p{Arabic}/u', (string) $text)) {
            $arabic = new \ArPHP\I18N\Arabic('Glyphs');
            return $arabic->utf8Glyphs((string) $text) . "\u{00A0}\u{00A0}\u{00A0}";
        }
        return $text;
    };

    $renderRow = function ($labelHtml, $valueHtml, $isHeader = false) use ($isPdfActive) {
        $tag = $isHeader ? 'th' : 'td';
        if ($isPdfActive) {
            return '<tr>'
                . '<td class="value" style="text-align: left; padding: 2px 4px 2px 0;">' . $valueHtml . '</td>'
                . '<' . $tag . ' style="text-align: right; padding: 2px 0px 2px 4px; font-weight: ' . ($isHeader ? '700' : 'normal') . '; font-size: ' . ($isHeader ? '12px' : '13px') . ';">' . $labelHtml . '</' . $tag . '>'
                . '</tr>';
        } else {
            return '<tr>'
                . '<' . $tag . ' style="text-align: right; padding: 2px 4px; font-weight: ' . ($isHeader ? '700' : 'normal') . '; font-size: ' . ($isHeader ? '12px' : '13px') . ';">' . $labelHtml . '</' . $tag . '>'
                . '<td class="value" style="text-align: left; padding: 2px 4px;">' . $valueHtml . '</td>'
                . '</tr>';
        }
    };
@endphp
<div class="receipt">
    @php
        $logoUrl = $logoPath;
        if (filled($logoPath) && !str_starts_with($logoPath, 'data:') && !str_starts_with($logoPath, 'http://') && !str_starts_with($logoPath, 'https://')) {
            $logoUrl = tenant_asset($logoPath);
        }
    @endphp
    @if(!empty($logoUrl))
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ $logoUrl }}" alt="Logo" style="max-width: 150px; height: auto;">
        </div>
    @endif

    <div class="center title">{{ $shopName !== '' ? $shape($shopName) : $shape('فاتورة مبيعات') }}</div>
    <div class="center muted">{{ $shape('فاتورة مبيعات / تقفيل جلسة') }}</div>

    <div class="line"></div>

    <table>
        {!! $renderRow($shape('رقم الفاتورة'), $printedInvoiceNo !== '' ? $printedInvoiceNo : $invoiceNumber, true) !!}
        {!! $renderRow($shape('التاريخ'), $invoice->issued_at ? app(\App\Support\Tenant\TenantClock::class)->utcToLocal($invoice->issued_at)->format('Y-m-d h:i A') : '-', true) !!}
        @if($invoice->device)
            {!! $renderRow($shape('الجهاز'), $shape($invoice->device->name), true) !!}
        @endif
        {!! $renderRow($shape('العميل'), $shape($invoice->customer?->name ?? '-'), true) !!}
        {!! $renderRow($shape('الخزينة'), $shape($invoice->cashBox?->name ?? '-'), true) !!}
        {!! $renderRow($shape('الكاشير'), $shape($invoice->createdBy?->name ?? '-'), true) !!}
    </table>

    @if($invoice->playSessionDetails->isNotEmpty())
        <div class="line"></div>
        <div class="section-title">{{ $shape('تفاصيل اللعب') }}</div>
        <table>
            @foreach ($invoice->playSessionDetails as $detail)
                @php
                    $detailLabel = $shape($detail->play_type ?: 'لعب') . ($detail->play_category ? ' (' . $shape($detail->play_category) . ')' : '')
                        . '<br><span class="muted">' . $shape('المدة:') . ' ' . (int) ($detail->period ?? 0) . ' ' . $shape('د') . ' | ' . $shape($detail->device?->name ?? $invoice->device?->name ?? '-') . '</span>';
                    $detailValue = number_format((float) ($detail->cost ?? 0), 2);
                @endphp
                {!! $renderRow($detailLabel, $detailValue) !!}
            @endforeach
        </table>
    @endif

    @if($invoice->orderSessionDetails->isNotEmpty())
        <div class="line"></div>
        <div class="section-title">{{ $shape('تفاصيل الطلبات') }}</div>
        <table>
            @foreach ($invoice->orderSessionDetails as $order)
                @php
                    $orderLabel = $shape($order->catalogItem?->name ?? 'صنف') . ' x' . (int) ($order->quantity ?? 0);
                    $orderValue = number_format((float) ($order->total ?? 0), 2);
                @endphp
                {!! $renderRow($orderLabel, $orderValue) !!}
            @endforeach
        </table>
    @endif

    <div class="line"></div>

    <table class="summary">
        @if((float) $invoice->play_amount > 0)
            {!! $renderRow($shape('إجمالي اللعب'), number_format((float) $invoice->play_amount, 2)) !!}
        @endif
        @if((float) $invoice->order_amount > 0)
            {!! $renderRow($shape('إجمالي الطلبات'), number_format((float) $invoice->order_amount, 2)) !!}
        @endif
        {!! $renderRow($shape('الإجمالي قبل الخصم'), number_format((float) $invoice->subtotal, 2)) !!}
        @if ($discountAmount > 0)
            {!! $renderRow($shape('الخصم'), number_format($discountAmount, 2)) !!}
        @endif
        @if ($serviceAmount > 0)
            {!! $renderRow($shape('الخدمة'), number_format($serviceAmount, 2)) !!}
        @endif
        @if ($taxAmount > 0)
            {!! $renderRow($shape('الضريبة'), number_format($taxAmount, 2)) !!}
        @endif
        {!! $renderRow($shape('المقدم'), number_format((float) $invoice->deposit_amount, 2)) !!}
        {!! $renderRow($shape('الصافي'), number_format((float) $invoice->final_amount, 2), true) !!}
        {!! $renderRow($shape('المدفوع'), number_format((float) $invoice->paid_amount, 2)) !!}
        {!! $renderRow($shape('المتبقي / الرصيد'), number_format((float) $invoice->remain_amount, 2)) !!}
    </table>

    @if (filled($footerMessage))
        <div class="line"></div>
        <div class="footer">{{ $shape($footerMessage) }}</div>
    @endif

    <div class="footer small">{{ now()->format('Y-m-d h:i:s A') }}</div>
</div>

@if(!isset($noPrint) || !$noPrint)
<script>
    window.addEventListener('load', function () {
        const params = new URLSearchParams(window.location.search);
        const silentMode = params.get('silent') === '1';

        // Check if we need image-fallback printing
        const fallback = params.get('fallback') === '1';
        if (fallback) {
            // Wait for images to load
            const images = document.querySelectorAll('img');
            let loadedCount = 0;
            const totalImages = images.length;

            const startPrintProcess = () => {
                // If it is in iframe or we just need window.print()
                if (silentMode) {
                    try {
                        window.print();
                        setTimeout(() => {
                            window.parent.postMessage('print-finished', '*');
                        }, 500);
                    } catch (e) {
                        window.parent.postMessage('print-finished', '*');
                    }
                } else {
                    window.print();
                }
            };

            if (totalImages === 0) {
                startPrintProcess();
            } else {
                images.forEach((img) => {
                    if (img.complete) {
                        loadedCount++;
                        if (loadedCount === totalImages) {
                            startPrintProcess();
                        }
                    } else {
                        img.addEventListener('load', () => {
                            loadedCount++;
                            if (loadedCount === totalImages) {
                                startPrintProcess();
                            }
                        });
                        img.addEventListener('error', () => {
                            loadedCount++;
                            if (loadedCount === totalImages) {
                                startPrintProcess();
                            }
                        });
                    }
                });
            }
            return;
        }

        if (silentMode) {
            window.print();
            setTimeout(function () {
                window.close();
            }, 500);
        } else {
            window.print();
        }
    });
</script>
@endif
</body>
</html>
