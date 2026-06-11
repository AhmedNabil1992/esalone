<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'PS Web') }} | Landing</title>
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
            }

            .container {
                max-width: 1080px;
                margin: 0 auto;
                padding: 40px 20px 72px;
            }

            .hero {
                background: linear-gradient(135deg, #fff8ef, #fffbf5);
                border: 1px solid var(--line);
                border-radius: 24px;
                padding: 36px;
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
                margin: 0 0 12px;
                font-size: clamp(30px, 4.7vw, 52px);
                line-height: 1.15;
            }

            .sub {
                margin: 0;
                color: var(--muted);
                font-size: 18px;
                line-height: 1.7;
                max-width: 760px;
            }

            .section-title {
                margin: 40px 0 18px;
                font-size: 28px;
            }

            .grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 16px;
            }

            .card {
                background: var(--surface);
                border: 1px solid var(--line);
                border-radius: 18px;
                padding: 18px;
            }

            .card h3 {
                margin: 0 0 8px;
                font-size: 20px;
            }

            .card p {
                margin: 0;
                color: var(--muted);
                line-height: 1.6;
                font-size: 15px;
            }

            .price {
                display: flex;
                align-items: baseline;
                gap: 8px;
                margin-bottom: 10px;
            }

            .price .value {
                color: var(--accent-strong);
                font-size: 34px;
                font-weight: 800;
            }

            .price .unit {
                color: var(--muted);
                font-size: 14px;
            }

            .footer {
                margin-top: 28px;
                color: var(--muted);
                font-size: 14px;
            }

            @media (max-width: 900px) {
                .grid {
                    grid-template-columns: 1fr;
                }

                .hero {
                    padding: 24px;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <section class="hero">
                <span class="badge">PlayStation Cafe SaaS</span>
                <h1>منصة إدارة كافيهات البلايستيشن بنظام Multi-Tenant</h1>
                <p class="sub">
                    نظام واحد لإدارة الفروع، الجلسات، نقاط البيع، المخزون، وتقفيل الشفتات.
                    كل عميل له قاعدة بيانات منفصلة بالكامل لضمان العزل والأمان.
                </p>
            </section>

            <h2 class="section-title">المميزات المتاحة</h2>
            <section class="grid" aria-label="features">
                <article class="card">
                    <h3>إدارة الجلسات والوقت</h3>
                    <p>تشغيل وإيقاف الجلسات، التسعير حسب الباقة، وحساب تلقائي للدقائق والإضافات.</p>
                </article>
                <article class="card">
                    <h3>POS ومبيعات</h3>
                    <p>طلبات الكافيه، فواتير، مرتجعات، ودعم أكثر من خزنة داخل نفس الفرع.</p>
                </article>
                <article class="card">
                    <h3>المخزون والمستودعات</h3>
                    <p>متابعة الأصناف، التحويل بين المخازن، والتنبيهات قبل نفاد المنتجات.</p>
                </article>
                <article class="card">
                    <h3>إدارة الشيفت</h3>
                    <p>فتح وقفل شيفت الكاشير، فرق الجرد النقدي، وتقارير التسليم والاستلام.</p>
                </article>
                <article class="card">
                    <h3>تقارير تشغيلية</h3>
                    <p>تقارير يومية عن الإيرادات، أكثر الأجهزة استخدامًا، وأداء كل موظف.</p>
                </article>
                <article class="card">
                    <h3>عزل بيانات كامل</h3>
                    <p>لكل عميل قاعدة بيانات خاصة، بدون أي تداخل بين بيانات العملاء.</p>
                </article>
            </section>

            <h2 class="section-title">الأسعار</h2>
            <section class="grid" aria-label="pricing">
                <article class="card">
                    <div class="price">
                        <span class="value">199</span>
                        <span class="unit">جنيه / شهريًا</span>
                    </div>
                    <h3>Starter</h3>
                    <p>فرع واحد، حتى 5 أجهزة، ونقطة بيع أساسية.</p>
                </article>

                <article class="card">
                    <div class="price">
                        <span class="value">399</span>
                        <span class="unit">جنيه / شهريًا</span>
                    </div>
                    <h3>Growth</h3>
                    <p>حتى 3 فروع، شيفت متقدم، ومخزون متعدد.</p>
                </article>

                <article class="card">
                    <div class="price">
                        <span class="value">799</span>
                        <span class="unit">جنيه / شهريًا</span>
                    </div>
                    <h3>Enterprise</h3>
                    <p>فروع غير محدودة، صلاحيات متقدمة، وتقارير مخصصة.</p>
                </article>
            </section>

            <p class="footer">هذه الصفحة مخصصة للدومين الأساسي فقط لعرض الخدمات والأسعار.</p>
        </div>
    </body>
</html>
