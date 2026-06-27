<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>تجهيز الطلبات</title>
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
            padding: 3mm 2mm;
        }

        .center {
            text-align: center;
        }

        .title {
            font-size: 19px;
            font-weight: 700;
            margin-top: 5px;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .line {
            border-top: 1.5px dashed #000;
            margin: 8px 0;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 3px 0;
            font-size: 13px;
            font-weight: 700;
        }

        .info-label {
            text-align: right;
            width: 50%;
        }

        .info-value {
            text-align: left;
            width: 50%;
            direction: ltr;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            border: 1px solid #000;
        }

        .items-table th, .items-table td {
            border: 1px solid #000;
            padding: 6px 4px;
            font-size: 13px;
            font-weight: 700;
            vertical-align: middle;
        }

        .items-table th {
            background-color: #f2f2f2;
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body @if(!request()->has('fallback')) onload="window.print();" @endif>
    <div class="receipt">
        <div class="center title">تجهيز المطبخ</div>
        
        <div class="line"></div>
        
        <table class="info-table">
            <tr>
                <td class="info-label">غرفة \ ترابيزة</td>
                <td class="info-value">{{ $deviceName }}</td>
            </tr>
            <tr>
                <td class="info-label">وقت الطلب</td>
                <td class="info-value" style="text-transform: uppercase;">{{ $orderTime }}</td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;" class="text-right">إسم الصنف</th>
                    <th style="width: 20%;" class="text-center">الكمية</th>
                    <th style="width: 30%;" class="text-right">ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td class="text-right">{{ $order->catalogItem?->name ?? '—' }}</td>
                        <td class="text-center">{{ $order->quantity }}</td>
                        <td class="text-right">{{ $order->notes ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if(request()->has('fallback'))
    <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script>
        window.addEventListener('load', function () {
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
        });
    </script>
    @endif
</body>
</html>
