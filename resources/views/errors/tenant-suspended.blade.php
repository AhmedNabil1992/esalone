<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ config('app.name', 'PS Web') }} | الموقع متوقف مؤقتًا</title>
        <style>
            :root {
                color-scheme: light;
                --background: #f4f7fb;
                --surface: #ffffff;
                --text: #172033;
                --muted: #667085;
                --line: #dce3ee;
                --accent: #b54708;
                --accent-soft: #fff4e5;
                --accent-line: #fed7aa;
                --button: #243b64;
                --button-hover: #172b4d;
            }

            * {
                box-sizing: border-box;
            }

            body {
                min-height: 100vh;
                margin: 0;
                display: grid;
                place-items: center;
                padding: 24px;
                overflow-x: hidden;
                color: var(--text);
                background:
                    radial-gradient(circle at 12% 14%, rgba(36, 59, 100, 0.10), transparent 30%),
                    radial-gradient(circle at 88% 84%, rgba(181, 71, 8, 0.10), transparent 28%),
                    var(--background);
                font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            }

            .shell {
                width: min(760px, 100%);
            }

            .panel {
                position: relative;
                overflow: hidden;
                padding: clamp(28px, 6vw, 56px);
                border: 1px solid var(--line);
                border-radius: 28px;
                background: var(--surface);
                box-shadow: 0 24px 70px rgba(36, 59, 100, 0.12);
            }

            .panel::before {
                position: absolute;
                inset: 0 0 auto;
                height: 5px;
                content: "";
                background: linear-gradient(90deg, var(--button), var(--accent));
            }

            .status-icon {
                width: 72px;
                height: 72px;
                display: grid;
                place-items: center;
                margin-bottom: 26px;
                border: 1px solid var(--accent-line);
                border-radius: 22px;
                color: var(--accent);
                background: var(--accent-soft);
            }

            .status-icon svg {
                width: 36px;
                height: 36px;
            }

            h1 {
                margin: 0;
                font-size: clamp(30px, 6vw, 46px);
                line-height: 1.25;
                letter-spacing: -0.02em;
            }

            .lead {
                max-width: 600px;
                margin: 18px 0 0;
                color: var(--muted);
                font-size: clamp(17px, 2.4vw, 20px);
                line-height: 1.9;
            }

            .notice {
                margin-top: 28px;
                padding: 18px 20px;
                border: 1px solid var(--line);
                border-radius: 16px;
                background: #f8fafc;
            }

            .notice strong {
                display: block;
                margin-bottom: 6px;
                font-size: 15px;
            }

            .notice p {
                margin: 0;
                color: var(--muted);
                line-height: 1.7;
            }

            .host {
                direction: ltr;
                display: inline-block;
                color: var(--text);
                font-weight: 700;
                word-break: break-all;
            }

            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
                margin-top: 30px;
            }

            .button {
                min-height: 48px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 11px 20px;
                border: 1px solid var(--line);
                border-radius: 13px;
                color: var(--text);
                background: #ffffff;
                font: inherit;
                font-weight: 700;
                text-decoration: none;
                cursor: pointer;
                transition: transform 160ms ease, border-color 160ms ease, background 160ms ease;
            }

            .button:hover {
                border-color: #aeb9ca;
                transform: translateY(-1px);
            }

            .button.primary {
                border-color: var(--button);
                color: #ffffff;
                background: var(--button);
            }

            .button.primary:hover {
                border-color: var(--button-hover);
                background: var(--button-hover);
            }

            .button:focus-visible {
                outline: 3px solid rgba(36, 59, 100, 0.24);
                outline-offset: 3px;
            }

            @media (max-width: 540px) {
                body {
                    padding: 14px;
                }

                .panel {
                    border-radius: 22px;
                }

                .status-icon {
                    width: 62px;
                    height: 62px;
                    border-radius: 18px;
                }

                .actions {
                    display: grid;
                }

                .button {
                    width: 100%;
                }
            }

            @media (prefers-reduced-motion: reduce) {
                .button {
                    transition: none;
                }
            }
        </style>
    </head>
    <body>
        <main class="shell" aria-labelledby="page-title">
            <section class="panel">
                <div class="status-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v4" />
                        <path d="M12 17h.01" />
                        <path d="M10.3 3.7 2.6 17a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 3.7a2 2 0 0 0-3.4 0Z" />
                    </svg>
                </div>

                <h1 id="page-title">الموقع متوقف مؤقتًا</h1>
                <p class="lead">
                    الخدمة غير متاحة لهذا الحساب في الوقت الحالي. إذا كنت صاحب الحساب، يرجى مراجعة حالة الاشتراك أو التواصل مع الجهة المسؤولة عن إدارة حسابك.
                </p>

                <div class="notice">
                    <strong>بيانات الموقع</strong>
                    <p>
                        الدومين: <span class="host">{{ $requestedHost }}</span><br>
                        بيانات الحساب محفوظة، وسيعود الموقع للعمل بعد إعادة تفعيل الخدمة.
                    </p>
                </div>

                <div class="actions">
                    <button class="button primary" type="button" onclick="window.location.reload()">
                        إعادة المحاولة
                    </button>
                    @if ($centralUrl)
                        <a class="button" href="{{ $centralUrl }}">العودة إلى الموقع الرئيسي</a>
                    @endif
                </div>
            </section>
        </main>
    </body>
</html>
