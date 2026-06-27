<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>فاتورة رقم {{ $invoice->invoice_no }}</title>
    <style>
        @page {
            size: 75mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html {
            width: 75mm;
            max-width: 75mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body {
            margin: 0;
            padding: 0;
            width: 75mm;
            max-width: 75mm;
            font-family: "Tahoma", "Arial", sans-serif;
            font-size: 13px;
            color: #000;
            line-height: 1.5;
            direction: rtl;
            text-align: right;
            transform: none !important;
            overflow: hidden;
        }

        .receipt {
            width: 71mm;
            max-width: 71mm;
            margin: 0 auto;
            padding: 2mm 1.5mm;
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
            padding: 2px 0;
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
                width: 75mm;
                max-width: 75mm;
                overflow: hidden;
            }

            .receipt {
                width: 71mm;
                max-width: 71mm;
                margin: 0 auto;
                padding: 2mm 1.5mm;
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
@endphp
<div class="receipt">
    @if (filled($logoPath))
        <img src="{{ tenant_asset($logoPath) }}" alt="Logo" class="logo" />
    @endif

    <div class="center title">{{ $shopName !== '' ? $shopName : 'فاتورة مبيعات' }}</div>
    <div class="center muted">فاتورة مبيعات / تقفيل جلسة</div>

    <div class="line"></div>

    <table>
        <tr>
            <th>رقم الفاتورة</th>
            <td class="value">{{ $printedInvoiceNo !== '' ? $printedInvoiceNo : $invoiceNumber }}</td>
        </tr>
        <tr>
            <th>التاريخ</th>
            <td class="value">{{ $invoice->issued_at?->format('Y-m-d h:i A') ?? '-' }}</td>
        </tr>
        @if($invoice->device)
        <tr>
            <th>الجهاز</th>
            <td>{{ $invoice->device->name }}</td>
        </tr>
        @endif
        <tr>
            <th>العميل</th>
            <td>{{ $invoice->customer?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>الخزينة</th>
            <td>{{ $invoice->cashBox?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>الكاشير</th>
            <td>{{ $invoice->createdBy?->name ?? '-' }}</td>
        </tr>
    </table>

    @if($invoice->playSessionDetails->isNotEmpty())
        <div class="section-title">تفاصيل اللعب</div>
        <table>
            @foreach ($invoice->playSessionDetails as $detail)
                <tr>
                    <td style="width:64%;">
                        {{ $detail->play_type ?: 'لعب' }} {{ $detail->play_category ? '(' . $detail->play_category . ')' : '' }}
                        <br>
                        <span class="muted">المدة: {{ (int) ($detail->period ?? 0) }} د |  {{ $detail->device?->name ?? $invoice->device?->name ?? '-' }}</span>
                    </td>
                    <td class="value">{{ number_format((float) ($detail->cost ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </table>
        <div class="line"></div>
    @endif

    @if($invoice->orderSessionDetails->isNotEmpty())
        <div class="section-title">تفاصيل الطلبات</div>
        <table>
            @foreach ($invoice->orderSessionDetails as $order)
                <tr>
                    <td style="width:64%;">{{ $order->catalogItem?->name ?? 'صنف' }} x{{ (int) ($order->quantity ?? 0) }}</td>
                    <td class="value">{{ number_format((float) ($order->total ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </table>
        <div class="line"></div>
    @endif

    <table class="summary">
        @if((float) $invoice->play_amount > 0)
            <tr><td>إجمالي اللعب</td><td class="value">{{ number_format((float) $invoice->play_amount, 2) }}</td></tr>
        @endif
        @if((float) $invoice->order_amount > 0)
            <tr><td>إجمالي الطلبات</td><td class="value">{{ number_format((float) $invoice->order_amount, 2) }}</td></tr>
        @endif
        <tr><td>الإجمالي قبل الخصم</td><td class="value">{{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if ($discountAmount > 0)
            <tr><td>الخصم</td><td class="value">{{ number_format($discountAmount, 2) }}</td></tr>
        @endif
        @if ($serviceAmount > 0)
            <tr><td>الخدمة</td><td class="value">{{ number_format($serviceAmount, 2) }}</td></tr>
        @endif
        @if ($taxAmount > 0)
            <tr><td>الضريبة</td><td class="value">{{ number_format($taxAmount, 2) }}</td></tr>
        @endif
        <tr><td>المقدم</td><td class="value">{{ number_format((float) $invoice->deposit_amount, 2) }}</td></tr>
        <tr class="total"><td>الصافي</td><td class="value">{{ number_format((float) $invoice->final_amount, 2) }}</td></tr>
        <tr><td>المدفوع</td><td class="value">{{ number_format((float) $invoice->paid_amount, 2) }}</td></tr>
        <tr><td>المتبقي / الرصيد</td><td class="value">{{ number_format((float) $invoice->remain_amount, 2) }}</td></tr>
    </table>

    @if (filled($footerMessage))
        <div class="line"></div>
        <div class="footer">{{ $footerMessage }}</div>
    @endif

    <div class="footer small">{{ now()->format('Y-m-d h:i:s A') }}</div>
</div>

<script>
    window.addEventListener('load', function () {
        const params = new URLSearchParams(window.location.search);
        const silentMode = params.get('silent') === '1';
        const isFallback = params.get('fallback') === '1';

        if (silentMode) {
            // Silent mode is handled by the extension/bridge only.
            return;
        }

        if (isFallback) {
            const receiptEl = document.querySelector('.receipt');
            if (!receiptEl) return;

            // Force receipt container overflow to visible for capturing
            receiptEl.style.overflow = 'visible';

            setTimeout(() => {
                html2canvas(receiptEl, {
                    scale: 3, // High scale for clear printing
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    scrollX: 0,
                    scrollY: 0,
                    windowWidth: document.documentElement.offsetWidth,
                    windowHeight: document.documentElement.offsetHeight
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    
                    // Create print container
                    const printContainer = document.createElement('div');
                    printContainer.style.width = '75mm';
                    printContainer.style.margin = '0';
                    printContainer.style.padding = '0';
                    printContainer.style.background = '#fff';

                    const printImg = document.createElement('img');
                    printImg.src = imgData;
                    printImg.style.width = '71mm';
                    printImg.style.display = 'block';
                    printImg.style.margin = '0 auto';
                    
                    printContainer.appendChild(printImg);

                    // Set HTML/Body to LTR and overflow visible for the image display
                    document.documentElement.dir = 'ltr';
                    document.documentElement.style.overflow = 'visible';
                    document.documentElement.style.height = 'auto';
                    
                    document.body.innerHTML = '';
                    document.body.dir = 'ltr';
                    document.body.style.margin = '0';
                    document.body.style.padding = '0';
                    document.body.style.overflow = 'visible';
                    document.body.style.height = 'auto';
                    document.body.style.background = '#fff';
                    
                    document.body.appendChild(printContainer);
                    
                    // Trigger print
                    window.print();
                });
            }, 300);
            return;
        }

        window.print();
    });
</script>
@if(request()->has('fallback'))
<script src="{{ asset('js/html2canvas.min.js') }}"></script>
@endif
</body>
</html>
