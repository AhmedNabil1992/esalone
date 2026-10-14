<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f7f3ed">
    <title>تفاصيل طلب الموعد | {{ $shopName }}</title>
    <style>
        :root{--bg:#f7f3ed;--surface:#fffdf9;--text:#302a25;--muted:#786f67;--line:#e9e1d8;--accent:#9a6c45;--danger:#a33a35}
        *{box-sizing:border-box}
        body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,sans-serif;line-height:1.7}
        a{color:inherit;text-decoration:none}
        .container{width:min(680px,calc(100% - 32px));margin:0 auto}
        header{padding:24px 0}
        .brand{font-size:1.15rem;font-weight:700}
        main{padding:12px 0 56px}
        .confirmation-card{padding:clamp(22px,5vw,40px);border:1px solid var(--line);border-radius:22px;background:var(--surface);box-shadow:0 22px 55px rgba(64,45,30,.08)}
        .status{display:inline-flex;padding:6px 12px;border-radius:999px;background:#f7efdf;color:#765035;font-weight:700}
        h1{margin:16px 0 8px;font-size:clamp(1.7rem,5vw,2.3rem);line-height:1.3}
        .intro{margin:0 0 24px;color:var(--muted)}
        dl{display:grid;gap:0;margin:0}
        .detail{display:flex;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--line)}
        dt{color:var(--muted)}
        dd{margin:0;text-align:left;font-weight:700}
        .service-list{margin:20px 0 0;padding:0 20px}
        .service-list li{margin:5px 0}
        .actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:25px}
        .button{display:inline-flex;min-height:46px;align-items:center;justify-content:center;padding:0 18px;border:0;border-radius:10px;background:var(--text);color:#fff;font:inherit;font-weight:700;cursor:pointer}
        .button-danger{background:var(--danger)}
        .back-link{display:inline-block;margin-top:20px;color:var(--accent);font-weight:700}
        .field-error{color:var(--danger);font-size:.84rem}
        @media(max-width:480px){.detail{align-items:flex-start;flex-direction:column;gap:2px}.detail dd{text-align:right}.actions>*{width:100%}}
    </style>
</head>
<body>
    <header class="container"><a class="brand" href="{{ route('tenant.landing') }}">{{ $appointment->branch->name }}</a></header>
    <main class="container">
        <section class="confirmation-card" aria-labelledby="confirmation-title">
            <span class="status">{{ match($appointment->status) {'requested' => 'بانتظار تأكيد الصالون', 'confirmed' => 'تم تأكيد الموعد', 'cancelled' => 'تم إلغاء الموعد', default => 'حالة الموعد: '.$appointment->status} }}</span>
            <h1 id="confirmation-title">{{ $appointment->status === 'cancelled' ? 'طلب الموعد ملغي' : 'تفاصيل طلب موعدك' }}</h1>
            <p class="intro">{{ $appointment->status === 'requested' ? 'احتفظ بهذا الرابط لمتابعة طلبك أو إلغائه. سيتواصل معك الصالون لتأكيد الوقت.' : 'يمكنك الرجوع إلى هذه الصفحة للاطلاع على حالة طلبك.' }}</p>

            <dl>
                <div class="detail"><dt>رقم الطلب</dt><dd>#{{ $appointment->getKey() }}</dd></div>
                <div class="detail"><dt>الفرع</dt><dd>{{ $appointment->branch->name }}</dd></div>
                <div class="detail"><dt>الموظف المفضل</dt><dd>{{ $appointment->staffMember->name }}</dd></div>
                <div class="detail"><dt>الوقت المطلوب ({{ $timeZone }})</dt><dd>{{ $appointment->start_at->copy()->timezone($timeZone)->format('Y-m-d h:i A') }}</dd></div>
                <div class="detail"><dt>المدة المتوقعة</dt><dd>{{ $appointment->duration_minutes }} دقيقة</dd></div>
                <div class="detail"><dt>الإجمالي التقديري</dt><dd>{{ number_format((float) $appointment->total_price, 2) }} ج.م</dd></div>
            </dl>

            <ul class="service-list">
                @foreach($appointment->items as $item)
                    <li>{{ $item->service_name }} — {{ number_format((float) $item->unit_price, 2) }} ج.م</li>
                @endforeach
            </ul>

            @if(in_array($appointment->status, ['requested', 'confirmed'], true) && $appointment->invoice_id === null && $appointment->start_at->isFuture())
                <form class="actions" method="POST" action="{{ route('tenant.bookings.cancel', ['token' => $token]) }}" onsubmit="return confirm('هل تريد إلغاء طلب الموعد؟');">
                    @csrf
                    <button class="button button-danger" type="submit">إلغاء طلب الموعد</button>
                    @error('appointment')<span class="field-error">{{ $message }}</span>@enderror
                </form>
            @endif

            <a class="back-link" href="{{ route('tenant.landing') }}">العودة إلى صفحة الصالون</a>
        </section>
    </main>
</body>
</html>
