<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة صالات البلايستيشن والكافيه | PS Web</title>
    <meta name="description" content="النظام الذكي المتكامل لإدارة صالات البلايستيشن ومبيعات الكافيه، تقارير مباشرة وتنبيهات فواتير ومخزون تلقائية عبر الواتساب.">
    
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #0b0f19;
            --bg-card: #131a26;
            --border-color: rgba(255, 255, 255, 0.08);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary: #3b82f6;
            --primary-glow: rgba(59, 130, 246, 0.35);
            --whatsapp: #10b981;
            --whatsapp-glow: rgba(16, 185, 129, 0.35);
            --accent: #f59e0b;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Ambient Background Glows */
        .glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .glow-2 {
            position: absolute;
            top: 40%;
            right: -10%;
            width: 40vw;
            height: 40vw;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.12) 0%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar Style */
        header {
            border-bottom: 1px solid var(--border-color);
            background: rgba(11, 15, 25, 0.8);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 80px;
        }

        .logo {
            font-size: 24px;
            font-weight: 900;
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo span {
            color: var(--accent);
            -webkit-text-fill-color: initial;
        }

        .btn-register {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            padding: 10px 24px;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 4px 15px var(--primary-glow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }

        /* Hero Section */
        .hero {
            padding: 96px 0 64px;
            text-align: center;
            position: relative;
        }

        .hero-badge {
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 6px 18px;
            border-radius: 99px;
            font-size: 14px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .hero-badge .dot {
            width: 8px;
            height: 8px;
            background-color: var(--whatsapp);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--whatsapp);
        }

        .hero h1 {
            font-size: clamp(32px, 5vw, 56px);
            font-weight: 900;
            line-height: 1.25;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #ffffff 60%, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: clamp(16px, 2.2vw, 20px);
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto 36px;
            font-weight: 400;
        }

        .hero-ctas {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff;
            padding: 14px 36px;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 20px var(--primary-glow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(59, 130, 246, 0.5);
        }

        .btn-whatsapp {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 14px 36px;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 20px var(--whatsapp-glow);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255,255,255,0.15);
        }

        .btn-whatsapp:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(16, 185, 129, 0.5);
        }

        /* Features Section */
        .section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 48px;
        }

        .section-header h2 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-header p {
            color: var(--text-secondary);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(59, 130, 246, 0.3);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.05);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .feature-card.wa-highlight {
            border-color: rgba(16, 185, 129, 0.2);
        }

        .feature-card.wa-highlight:hover {
            border-color: rgba(16, 185, 129, 0.4);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.05);
        }

        .feature-card.wa-highlight .feature-icon {
            background: rgba(16, 185, 129, 0.1);
            color: var(--whatsapp);
        }

        .feature-card h3 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--text-secondary);
            font-size: 15px;
            line-height: 1.7;
        }

        /* Pricing Section */
        .pricing-section {
            background: radial-gradient(circle at 50% 50%, rgba(59, 130, 246, 0.04) 0%, transparent 60%);
            padding: 80px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .pricing-card {
            background-color: var(--bg-card);
            border: 2px solid var(--primary);
            border-radius: 24px;
            max-width: 600px;
            margin: 0 auto;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .pricing-badge {
            background: var(--primary);
            color: #fff;
            padding: 6px 18px;
            border-radius: 99px;
            font-size: 13px;
            font-weight: 800;
            position: absolute;
            top: -15px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 4px 10px var(--primary-glow);
        }

        .pricing-plan-title {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .pricing-plan-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 32px;
        }

        .price-options {
            display: flex;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            margin-bottom: 32px;
        }

        .price-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 20px 32px;
            min-width: 180px;
        }

        .price-box.popular {
            border-color: rgba(245, 158, 11, 0.4);
            background: rgba(245, 158, 11, 0.03);
        }

        .price-box .price-value {
            font-size: 32px;
            font-weight: 900;
            color: var(--text-primary);
        }

        .price-box .price-currency {
            font-size: 16px;
            font-weight: 700;
            color: var(--accent);
        }

        .price-box .price-period {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 4px;
            font-weight: 600;
        }

        .features-list {
            text-align: right;
            max-width: 400px;
            margin: 0 auto 36px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            font-weight: 600;
        }

        .feature-item span.icon {
            color: var(--whatsapp);
            font-size: 18px;
        }

        /* CTA Section */
        .cta-banner {
            padding: 80px 0;
            text-align: center;
            position: relative;
        }

        .cta-content {
            background: linear-gradient(135deg, #131a26, #0c111a);
            border: 1px solid var(--border-color);
            border-radius: 28px;
            padding: 56px 32px;
            max-width: 900px;
            margin: 0 auto;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .cta-content h2 {
            font-size: 30px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .cta-content p {
            color: var(--text-secondary);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto 32px;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border-color);
            padding: 32px 0;
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
        }

        @media (max-width: 768px) {
            .hero {
                padding: 64px 0 40px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .pricing-card {
                padding: 36px 20px;
            }

            .cta-content {
                padding: 40px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="glow-1"></div>
    <div class="glow-2"></div>

    <!-- Header Navigation -->
    <header>
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    PS <span>Web</span>
                </div>
                <a href="https://etech-valley.com/customer" class="btn-register">تسجيل حساب جديد</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-badge">
                <span class="dot"></span>
                ربط مباشر وتنبيهات فوراً عبر الواتساب
            </div>
            <h1>النظام الذكي المتكامل لإدارة صالات البلايستيشن والكافيه</h1>
            <p>حل متكامل واحترافي يغنيك عن الدفاتر والتعقيد. تحكم بأجهزتك، وراقب مبيعات الكافيه والمخزون، وتابع تقارير أرباحك وجرد الشفتات من أي مكان، مع ميزة إرسال الفواتير والتنبيهات التلقائية مباشرة للعملاء والمديرين عبر الواتساب.</p>
            <div class="hero-ctas">
                <a href="https://etech-valley.com/customer" class="btn-primary">ابدأ الآن مجاناً</a>
                <a href="https://wa.me/201558787680" target="_blank" class="btn-whatsapp">
                    <svg style="width: 20px; height: 20px; fill: currentColor;" viewBox="0 0 24 24">
                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.733-1.455L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.59 1.978 14.12 .952 11.5 .952c-5.442 0-9.866 4.372-9.87 9.802 0 1.714.463 3.39 1.337 4.842l-.994 3.63 3.734-.968c1.452.8 3.03 1.22 4.62 1.222zm11.367-7.8c-.29-.145-1.71-.845-1.973-.94-.265-.1-.456-.145-.648.145-.19.29-.74.94-.907 1.133-.167.19-.334.217-.624.072-1.36-.682-2.274-1.21-3.188-2.775-.24-.412.24-.38.687-1.27.082-.162.04-.303-.02-.447-.06-.145-.648-1.56-.888-2.14-.234-.56-.47-.483-.648-.492-.17-.008-.363-.01-.557-.01-.19 0-.502.07-.765.362-.263.29-1.004.98-1.004 2.39s1.034 2.78 1.18 2.97c.145.19 2.034 3.104 4.93 4.354.687.297 1.224.475 1.64.607.69.219 1.32.188 1.82.114.557-.082 1.71-.7 1.953-1.378.24-.678.24-1.26.168-1.378-.072-.12-.263-.19-.554-.336z"/>
                    </svg>
                    تواصل واتساب للاستفسار
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section">
        <div class="container">
            <div class="section-header">
                <h2>كل ما تحتاجه لإدارة صالتك باحترافية</h2>
                <p>نظام متكامل ومدروس بعناية ليغطي كافة جوانب عمل صالات البلايستيشن والكافيه والأنشطة الترفيهية.</p>
            </div>
            
            <div class="features-grid">
                <!-- Feature 1: Hardware control -->
                <div class="feature-card wa-highlight">
                    <div class="feature-icon">🔒</div>
                    <h3>نظام التحكم بالأجهزة (البريدج)</h3>
                    <p>التحكم المطلق ومنع التلاعب! ربط أجهزتك برمجياً بأجهزة التحكم بالكهرباء والشاشات (Bridge)، ليتم تشغيل وإطفاء الشاشة تلقائياً عند بدء وإنهاء الوقت من السيستم لضمان عدم لعب دقيقة واحدة بدون تسجيل فاتورة.</p>
                </div>
                <!-- Feature 2: Customer Portal -->
                <div class="feature-card">
                    <div class="feature-icon">🌐</div>
                    <h3>موقع مخصص وبوابة لعملائك</h3>
                    <p>موقع خاص يحمل هوية صالتك التجارية، يستطيع عملاؤك من خلاله تسجيل الدخول لمتابعة فواتيرهم، مراجعة رصيد محفظتهم المالية، تتبع نقاط الولاء واستبدالها بمكافآت وجلسات مجانية.</p>
                </div>
                <!-- Feature 3: Pricing Tiers -->
                <div class="feature-card">
                    <div class="feature-icon">📈</div>
                    <h3>تسعير مرن وفئات للعملاء وعروض</h3>
                    <p>متاح إعداد أسعار مختلفة للأجهزة على مدار اليوم (سعر صباحي وسعر مسائي)، وتصنيف العملاء لفئات (VIP، زائر دائم، عادي)، وعرض عروضك الترويجية وباقات الخصم بشكل جذاب على موقعك للعملاء.</p>
                </div>
                <!-- Feature 4: Online Menu -->
                <div class="feature-card">
                    <div class="feature-icon">🍽️</div>
                    <h3>منيو أونلاين تفاعلي (Online Menu)</h3>
                    <p>منيو كافيه رقمي للزبائن يتصفحونه من هواتفهم، يتم تحديثه ومزامنته تلقائياً وبشكل لحظي بناءً على الأصناف والأسعار وكميات المخزون المتاحة داخل مخازن السيستم الخاص بك.</p>
                </div>
                <!-- Feature 5: WhatsApp Invoices -->
                <div class="feature-card wa-highlight">
                    <div class="feature-icon">💬</div>
                    <h3>فواتير العملاء التلقائية عبر الواتساب</h3>
                    <p>بمجرد إغلاق الجلسة وتقفيل الحساب، يتم توليد فاتورة منسقة وإرسالها فوراً إلى هاتف العميل عبر الواتساب تلقائياً، مع دعم الطباعة المباشرة على طابعات الكاشير 80 مم.</p>
                </div>
                <!-- Feature 6: WhatsApp Inventory alerts -->
                <div class="feature-card wa-highlight">
                    <div class="feature-icon">🚨</div>
                    <h3>تنبيهات المخازن للمديرين بالواتساب</h3>
                    <p>يبقيك النظام في أمان تام. يستقبل الملاك إشعارات فورية على الواتساب عند اقتراب أي صنف من النفاد، مع تقارير وإحصائيات دورية لأداء المحل.</p>
                </div>
                <!-- Feature 7: POS -->
                <div class="feature-card">
                    <div class="feature-icon">☕</div>
                    <h3>كاشير الكافيه والمبيعات (POS)</h3>
                    <p>نظام مبيعات متكامل لإضافة الطلبات والمشروبات إلى الأجهزة أثناء الجلسة أو البيع المباشر، وتوافق تام مع طابعات الفواتير الحرارية كاشير 8 سم.</p>
                </div>
                <!-- Feature 8: Shifts -->
                <div class="feature-card">
                    <div class="feature-icon">🔑</div>
                    <h3>إغلاق الورديات وجرد النقدية</h3>
                    <p>متابعة جرد درج الكاشير وتسليم الشفتات بدقة متناهية. مقارنة الرصيد الفعلي بالرصيد الدفتري المتوقع وحساب المصروفات والمشتريات الخارجية.</p>
                </div>
                <!-- Feature 9: Reservations -->
                <div class="feature-card">
                    <div class="feature-icon">📅</div>
                    <h3>تقويم وجدول الحجوزات</h3>
                    <p>حجز الأجهزة والغرف مسبقاً للعملاء مع فحص تلقائي لتعارض الأوقات (Check Overlap) وعرضها في تقويم تفاعلي منظم لتجنب أي إحراج.</p>
                </div>
                <!-- Feature 10: Waiting list -->
                <div class="feature-card">
                    <div class="feature-icon">⏳</div>
                    <h3>قائمة الانتظار الذكية</h3>
                    <p>تنظيم زوار الصالة في قائمة انتظار حسب نوع الجهاز المطلوبة، مع حساب تقديري لزمن الانتظار المتبقي وتنبيه الكاشير عند خلو الأجهزة المناسبة.</p>
                </div>
                <!-- Feature 11: Multi-Warehouse -->
                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h3>المخازن والمستودعات المتعددة</h3>
                    <p>إدارة أكثر من مخزن، جرد الأصناف، متابعة التحويلات الداخلية للبضائع، مع تتبع كامل لفواتير المشتريات وتكلفة البضاعة المباعة للتأكد من أرباحك.</p>
                </div>
                <!-- Feature 12: AI Assistant -->
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>المساعد الذكي بالذكاء الاصطناعي</h3>
                    <p>دردشة ذكية ومدمجة تجيب الملاك عن أرقام الأرباح، حالة الأجهزة، النواقص، وتسمح لك بتعديل الأسعار والعروض عبر أوامر نصية سريعة.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="pricing-section">
        <div class="container">
            <div class="section-header">
                <h2>خطط تسعير بسيطة بدون تعقيد</h2>
                <p>باقة واحدة متكاملة تمنحك الوصول لجميع المميزات دون أي رسوم خفية.</p>
            </div>

            <div class="pricing-card">
                <div class="pricing-badge">الباقة الشاملة</div>
                <h3 class="pricing-plan-title">باقة إدارة الصالة والكافيه</h3>
                <p class="pricing-plan-subtitle">دخول كامل لجميع أدوات النظام وشاشات التحكم مع تفعيل ربط الواتساب</p>
                
                <div class="price-options">
                    <!-- Monthly -->
                    <div class="price-box">
                        <span class="price-value">200</span>
                        <span class="price-currency">ج.م</span>
                        <span class="price-period">شهرياً</span>
                    </div>
                    <!-- Yearly -->
                    <div class="price-box popular">
                        <span class="price-value">2000</span>
                        <span class="price-currency">ج.م</span>
                        <span class="price-period">سنوياً (وفر 400 ج.م)</span>
                    </div>
                </div>

                <div class="features-list">
                    <div class="feature-item">
                        <span class="icon">✓</span> أجهزة وصالات لعب غير محدودة
                    </div>
                    <div class="feature-item">
                        <span class="icon">✓</span> نظام كاشير ومبيعات الكافيه متكامل
                    </div>
                    <div class="feature-item">
                        <span class="icon">✓</span> ربط مباشر بالواتساب لإرسال فواتير العملاء
                    </div>
                    <div class="feature-item">
                        <span class="icon">✓</span> تنبيهات المخزون والتقارير الفورية للملاك عبر الواتساب
                    </div>
                    <div class="feature-item">
                        <span class="icon">✓</span> تقفيل شفتات، جرد نقدية، وإدارة مصروفات
                    </div>
                    <div class="feature-item">
                        <span class="icon">✓</span> دعم فني وتحديثات مستمرة مجانية
                    </div>
                </div>

                <a href="https://etech-valley.com/customer" class="btn-primary" style="display: block; width: 100%; text-align: center;">سجل الآن وابدأ تجربة النظام</a>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
        <div class="container">
            <div class="cta-content">
                <h2>جاهز لتطوير وإدارة صالتك باحترافية؟</h2>
                <p>انضم إلينا اليوم وابدأ بتنظيم أعمالك ومبيعاتك بكفاءة ودون عناء. للتسجيل الفوري تفضل بزيارة رابط التسجيل أو تواصل معنا مباشرة عبر الواتساب للإجابة على استفساراتك.</p>
                <div class="hero-ctas">
                    <a href="https://etech-valley.com/customer" class="btn-primary">ابدأ التسجيل الآن</a>
                    <a href="https://wa.me/201558787680" target="_blank" class="btn-whatsapp">
                        تواصل معنا واتساب: 01558787680
                    </a>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer>
        <div class="container">
            <p>© {{ date('Y') }} PS Web. جميع الحقوق محفوظة.</p>
        </div>
    </footer>
</body>
</html>
