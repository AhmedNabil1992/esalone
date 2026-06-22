<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'PS Web') }} | Tenant Not Found</title>
        <style>
            :root {
                --bg: #f5efe6;
                --surface: #fff8ef;
                --ink: #1f2937;
                --muted: #6b7280;
                --accent: #b45309;
                --accent-strong: #92400e;
                --line: #e5d8c4;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: "Segoe UI", Tahoma, sans-serif;
                background:
                    radial-gradient(circle at 10% 10%, #fde68a 0, transparent 26%),
                    radial-gradient(circle at 85% 30%, #fdba74 0, transparent 28%),
                    var(--bg);
                color: var(--ink);
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 20px;
            }

            .card {
                width: min(820px, 100%);
                background: linear-gradient(135deg, #fff8ef, #fffbf5);
                border: 1px solid var(--line);
                border-radius: 24px;
                padding: 28px;
                box-shadow: 0 20px 50px rgba(146, 64, 14, 0.12);
            }

            .badge {
                display: inline-block;
                background: #ffedd5;
                color: var(--accent-strong);
                border: 1px solid #fdba74;
                padding: 8px 14px;
                border-radius: 999px;
                font-size: 13px;
                font-weight: 700;
                margin-bottom: 16px;
            }

            h1 {
                margin: 0 0 10px;
                font-size: clamp(26px, 4vw, 38px);
                line-height: 1.2;
            }

            p {
                margin: 0;
                color: var(--muted);
                line-height: 1.8;
                font-size: 17px;
            }

            .box {
                margin-top: 20px;
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 16px;
                padding: 16px;
            }

            .value {
                font-size: 20px;
                font-weight: 700;
                color: var(--accent-strong);
                word-break: break-word;
            }

            .actions {
                margin-top: 24px;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .btn {
                text-decoration: none;
                border-radius: 12px;
                padding: 10px 14px;
                font-weight: 700;
                border: 1px solid var(--line);
                color: var(--ink);
                background: #ffffff;
            }

            .btn.primary {
                border-color: var(--accent);
                background: var(--accent);
                color: #fff;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <span class="badge">Tenant Domain Check</span>
            <h1>هذا الدومين غير مرتبط بأي تينانت حالياً</h1>
            <p>
                الاسم المطلوب لا يملك تينانت مسجل بعد. يمكنك استخدام الاسم المتاح لإنشاء تينانت جديد.
            </p>

            <section class="box">
                <p>الاسم المقترح:</p>
                <p class="value">{{ $requestedName ?? 'غير محدد' }}</p>
            </section>

            <section class="box">
                <p>الدومين المطلوب:</p>
                <p class="value">{{ $requestedHost }}</p>
            </section>

            <div class="actions">
                @if ($centralDomain)
                    <a class="btn primary" href="http://{{ $centralDomain }}{{ $requestedName ? '?tenant_name=' . rawurlencode($requestedName) : '' }}">
                        الذهاب للدومين الأساسي
                    </a>
                @endif
                <a class="btn" href="javascript:history.back()">رجوع</a>
            </div>
        </main>
    </body>
</html>
