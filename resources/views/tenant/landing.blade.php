<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    
    <!-- خطوط جوجل -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #080c14;
            --card-bg: rgba(13, 20, 35, 0.75);
            --primary: #8b5cf6;
            --primary-glow: rgba(139, 92, 246, 0.4);
            --secondary: #3b82f6;
            --secondary-glow: rgba(59, 130, 246, 0.4);
            --accent: #10b981;
            --gold: #f59e0b;
            --gold-glow: rgba(245, 158, 11, 0.3);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(255, 255, 255, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', 'Outfit', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 5% 15%, rgba(139, 92, 246, 0.18) 0%, transparent 45%),
                radial-gradient(circle at 95% 85%, rgba(59, 130, 246, 0.18) 0%, transparent 45%);
            background-attachment: fixed;
        }

        /* حاوية الصفحات */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* الهيدر */
        header {
            padding: 16px 0;
            backdrop-filter: blur(16px);
            background-color: rgba(8, 12, 20, 0.85);
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logo-dot {
            width: 8px;
            height: 8px;
            background-color: var(--accent);
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 10px var(--accent);
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 24px;
        }

        nav a {
            color: var(--text-main);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 700;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary);
        }

        .auth-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 8px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 4px 14px var(--primary-glow);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(139, 92, 246, 0.5);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--text-muted);
            box-shadow: none;
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 0.8rem;
            border-radius: 8px;
        }

        /* الهيرو سكشن */
        .hero {
            padding: 90px 0 60px;
            text-align: center;
            position: relative;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 900;
            line-height: 1.25;
            margin-bottom: 18px;
            background: linear-gradient(135deg, #fff 40%, #cbd5e1 75%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 680px;
            margin: 0 auto 30px;
            line-height: 1.8;
            font-weight: 500;
        }

        .hero-btns {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        /* الإشعارات العائمة (Toasts) */
        .toast-container {
            position: fixed;
            top: 24px;
            left: 24px;
            right: 24px;
            max-width: 420px;
            z-index: 200;
        }

        .toast {
            background: rgba(13, 20, 35, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 14px;
            padding: 16px 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border: 1px solid var(--border-color);
        }

        .toast-success { border-right: 4px solid var(--accent); }
        .toast-error { border-right: 4px solid #ef4444; }

        /* لوحة العميل الشخصية (Customer Dashboard) */
        .dashboard-section {
            padding: 40px 0;
        }

        .dashboard-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 24px;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dashboard-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.1rem;
        }

        .user-meta h3 {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .user-meta p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--border-color);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255,255,255,0.1);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon-blue { background: rgba(59, 130, 246, 0.1); color: var(--secondary); border: 1px solid rgba(59, 130, 246, 0.2); }
        .stat-icon-gold { background: rgba(245, 158, 11, 0.1); color: var(--gold); border: 1px solid rgba(245, 158, 11, 0.2); }

        .stat-info p {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 700;
        }

        .stat-info h4 {
            font-size: 1.6rem;
            font-weight: 900;
            margin-top: 4px;
        }

        .convert-action-box {
            background: rgba(139, 92, 246, 0.05);
            border: 1px dashed rgba(139, 92, 246, 0.3);
            border-radius: 18px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .convert-text h4 {
            font-size: 0.95rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 4px;
        }

        .convert-text p {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* سكشن المميزات */
        .features {
            padding: 60px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background-color: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 10px 24px rgba(139, 92, 246, 0.08);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15), rgba(59, 130, 246, 0.15));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.4rem;
            margin-bottom: 16px;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }

        .feature-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.88rem;
            line-height: 1.6;
        }

        /* سكشن العروض */
        .offers {
            padding: 60px 0;
            background-color: rgba(13, 20, 35, 0.3);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .offer-card {
            background-color: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 22px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .offer-card:hover {
            transform: translateY(-4px);
            border-color: var(--secondary);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.12);
        }

        .offer-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .offer-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 10px;
        }

        .offer-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
        }

        .offer-price {
            font-size: 2rem;
            font-weight: 900;
            color: var(--accent);
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-top: 8px;
        }

        .offer-price span {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .offer-body {
            padding: 20px;
            flex-grow: 1;
        }

        .offer-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            font-size: 0.85rem;
            color: var(--text-main);
        }

        .offer-remark {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
            padding: 10px;
            font-size: 0.8rem;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            margin-top: 12px;
        }

        .offer-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
        }

        /* سكشن معرض الصور */
        .gallery {
            padding: 60px 0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }

        .gallery-item {
            height: 200px;
            border-radius: 18px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            transform: scale(1.02);
            border-color: var(--primary);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* سكشن التواصل والفوتر */
        footer {
            background-color: #04060b;
            padding: 50px 0 24px;
            border-top: 1px solid var(--border-color);
            font-size: 0.88rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-col h4 {
            font-size: 1rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
            position: relative;
            padding-bottom: 6px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 30px;
            height: 2px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .footer-about p {
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 16px;
        }

        .social-links {
            display: flex;
            gap: 10px;
        }

        .social-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-main);
            text-decoration: none;
            transition: all 0.3s;
        }

        .social-btn:hover {
            background: var(--primary);
            color: #fff;
            transform: translateY(-2px);
        }

        .contact-info-list {
            list-style: none;
        }

        .contact-info-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: var(--text-muted);
        }

        .footer-copyright {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.78rem;
        }

        /* استجابة الشاشات الصغيرة للموبايل */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.1rem;
            }
            .hero {
                padding: 50px 0 40px;
            }
            nav {
                display: none;
            }
            .header-content {
                gap: 12px;
            }
            .convert-action-box {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }
            .convert-action-box button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <!-- حاوية الإشعارات العائمة -->
    <div class="toast-container">
        @if(session('success'))
            <div class="toast toast-success" id="toast-success">
                <span>✔️</span>
                <div>{{ session('success') }}</div>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('toast-success');
                    if (el) el.style.display = 'none';
                }, 5000);
            </script>
        @endif

        @if(session('error'))
            <div class="toast toast-error" id="toast-error">
                <span>⚠️</span>
                <div>{{ session('error') }}</div>
            </div>
            <script>
                setTimeout(() => {
                    const el = document.getElementById('toast-error');
                    if (el) el.style.display = 'none';
                }, 5000);
            </script>
        @endif
    </div>

    <!-- الهيدر -->
    <header>
        <div class="container header-content">
            <div class="logo">
                <span class="logo-dot"></span>
                {{ $title }}
            </div>
            
            <nav>
                <ul>
                    <li><a href="#features">الخدمات</a></li>
                    <li><a href="#offers">العروض</a></li>
                    <li><a href="{{ route('tenant.menu') }}">المنيو 📋</a></li>
                    <li><a href="#gallery">المعرض</a></li>
                    <li><a href="#contact">اتصل بنا</a></li>
                </ul>
            </nav>

            <div class="auth-actions">
                @if(Auth::guard('customer')->check())
                    <div style="display:flex; align-items:center; gap:12px;">
                        <span style="font-size:0.8rem; font-weight:700; color:var(--gold); background:rgba(245,158,11,0.08); padding:4px 10px; border-radius:8px; border:1px solid rgba(245,158,11,0.2)">
                            {{ Auth::guard('customer')->user()->points }} نقطة
                        </span>
                        
                        <a href="/customer" class="btn btn-sm btn-outline">لوحة الحساب</a>

                        <form action="/customer/logout" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">خروج</button>
                        </form>
                    </div>
                @else
                    <a href="/customer/login" class="btn btn-outline btn-sm">تسجيل دخول</a>
                    <a href="/customer/register" class="btn btn-sm">إنشاء حساب</a>
                @endif
            </div>
        </div>
    </header>

    <!-- الهيرو سكشن -->
    <section class="hero">
        <div class="container">
            <h1 class="hero-title">{{ $title }}</h1>
            <p class="hero-desc">{{ $description }}</p>
            <div class="hero-btns">
                <a href="#offers" class="btn">اكتشف عروضنا القوية</a>
                @if(!Auth::guard('customer')->check())
                    <a href="/customer/register" class="btn btn-outline">سجل معنا واكسب نقاطاً</a>
                @else
                    <a href="#dashboard" class="btn btn-outline">لوحة حسابك والنقاط</a>
                @endif
            </div>
        </div>
    </section>

    <!-- لوحة بيانات العميل (Customer Dashboard) - تظهر فقط للأعضاء المسجلين -->
    @if(Auth::guard('customer')->check())
        @php $loggedCustomer = Auth::guard('customer')->user(); @endphp
        <section id="dashboard" class="dashboard-section">
            <div class="container">
                <div class="dashboard-card">
                    
                    <div class="dashboard-header">
                        <div class="dashboard-user">
                            <div class="user-avatar">
                                {{ mb_substr($loggedCustomer->name, 0, 1) }}
                            </div>
                            <div class="user-meta">
                                <h3>أهلاً بك، {{ $loggedCustomer->name }} 👋</h3>
                                <p>عضوية مسجلة برقم هاتف: {{ $loggedCustomer->phone }}</p>
                            </div>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-muted); font-weight:700;">
                            تاريخ الانضمام: {{ $loggedCustomer->created_at ? $loggedCustomer->created_at->format('Y-m-d') : 'اليوم' }}
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-blue">💵</div>
                            <div class="stat-info">
                                <p>رصيد المحفظة المتاح</p>
                                <h4>{{ number_format($loggedCustomer->balance, 2) }} <span style="font-size:0.9rem; font-weight:500;">ج.م</span></h4>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon stat-icon-gold">⭐</div>
                            <div class="stat-info">
                                <p>نقاط الولاء المجمعة</p>
                                <h4>{{ number_format($loggedCustomer->points, 0) }} <span style="font-size:0.9rem; font-weight:500;">نقطة</span></h4>
                            </div>
                        </div>
                    </div>

                    <div class="convert-action-box">
                        <div class="convert-text">
                            <h4>استبدال النقاط بمبالغ نقدية في محفظتك</h4>
                            <p>كل <strong>{{ $pointsRate }} نقاط</strong> تساوي <strong>1 جنيه</strong> رصيد في محفظتك. يمكنك استخدامه للعب أو شراء الطلبات لاحقاً!</p>
                        </div>
                        
                        <div style="width: 100%;">
                            @if($loggedCustomer->points > 0)
                                <form action="{{ route('tenant.customer.convert-points') }}" method="POST" style="margin:0; display:flex; align-items:center; gap:12px; flex-wrap:wrap; margin-top:10px;">
                                    @csrf
                                    <div style="display:flex; align-items:center; gap:8px;">
                                        <label style="font-size:0.85rem; font-weight:700; color:var(--text-muted); white-space:nowrap;">النقاط المراد استبدالها:</label>
                                        <input type="number" name="points" min="1" max="{{ $loggedCustomer->points }}" value="{{ $loggedCustomer->points }}" required 
                                            style="width:120px; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); color:#fff; border-radius:10px; padding:8px 12px; font-weight:700; outline:none; text-align:center;"
                                            oninput="document.getElementById('money-preview').innerText = (this.value > 0 ? (this.value / {{ $pointsRate }}).toFixed(2) : '0.00')">
                                    </div>
                                    <button type="submit" class="btn" style="background:linear-gradient(135deg, var(--gold), #d97706); box-shadow:0 4px 14px var(--gold-glow); font-weight:900;">
                                        تحويل إلى رصيد (<span id="money-preview">{{ round($loggedCustomer->points / $pointsRate, 2) }}</span> ج.م)
                                    </button>
                                </form>
                            @else
                                <button type="button" disabled style="background:#4b5563; color:#9ca3af; cursor:not-allowed;" class="btn">
                                    لا توجد نقاط كافية للتحويل حالياً
                                </button>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </section>
    @endif

    <!-- سكشن المميزات والخدمات -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">نظام المكافآت والخدمات</h2>
                <p class="section-subtitle">نوفر لك أرقى تجربة لعب ونكافئك على كل دقيقة تقضيها معنا!</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎁</div>
                    <h3>نقاط مكافآت مع كل زيارة</h3>
                    <p>قم بتسجيل حسابك برقم الهاتف، وكلما لعبت أو طلبت مشروباً يتم إضافة نقاط تلقائياً لحسابك.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">💰</div>
                    <h3>استبدال النقاط بفلوس</h3>
                    <p>حول نقاطك المجمعة من لوحة حسابك مباشرة إلى رصيد مالي حقيقي لتلعب به أو تشتري به أي طلبات.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎮</div>
                    <h3>أحدث الأجهزة والشاشات</h3>
                    <p>أجهزة PlayStation 5 جديدة مع شاشات 4K فائقة الدقة ومقاعد ألعاب مريحة لخصوصية تامة ومتعة قصوى.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🍹</div>
                    <h3>بوفيه مشروبات متكامل</h3>
                    <p>مجموعة واسعة من المشروبات الساخنة والباردة والمأكولات الخفيفة تقدم لك مباشرة في صالتك.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- سكشن العروض -->
    <section id="offers" class="offers">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">أقوى العروض الحالية</h2>
                <p class="section-subtitle">استفد من باقاتنا الخاصة ووفر أكثر أثناء اللعب</p>
            </div>

            <div class="offers-grid">
                @forelse($offers as $offer)
                    <div class="offer-card">
                        <div class="offer-header">
                            <div class="offer-badge">{{ $offer->play_type }}</div>
                            <h3 class="offer-title">{{ $offer->name }}</h3>
                            <div class="offer-price">
                                {{ number_format($offer->price, 0) }} <span>ج.م</span>
                            </div>
                        </div>
                        
                        <div class="offer-body">
                            <div class="offer-info-item">
                                ⏱️ 
                                <span style="margin-right:6px;">مدة العرض: <strong>{{ $offer->period }} دقيقة</strong></span>
                            </div>
                            @if($offer->time_from && $offer->time_to)
                                <div class="offer-info-item">
                                    🕒 
                                    <span style="margin-right:6px;">متاح من: <strong>{{ \Carbon\Carbon::parse($offer->time_from)->format('h:i A') }}</strong> إلى: <strong>{{ \Carbon\Carbon::parse($offer->time_to)->format('h:i A') }}</strong></span>
                                </div>
                            @endif
                            @if($offer->devices)
                                <div class="offer-info-item">
                                    🎮 
                                    <span style="margin-right:6px;">الأجهزة المشمولة: {{ $offer->devices }}</span>
                                </div>
                            @endif
                            @if($offer->remark)
                                <div class="offer-remark">
                                    💡 {{ $offer->remark }}
                                </div>
                            @endif
                        </div>

                        <div class="offer-footer">
                            @if($whatsapp)
                                <a href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('أريد الاستفسار عن عرض: ' . $offer->name) }}" target="_blank" class="btn" style="width:100%; justify-content:center;">
                                    احجز هذا العرض الآن
                                </a>
                            @else
                                <a href="#contact" class="btn btn-outline" style="width:100%; justify-content:center;">
                                    احجز الآن
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div style="grid-column:1/-1; text-align:center; padding:50px 0;">
                        <p style="color:var(--text-muted); font-size:1.1rem; font-weight:700;">لا توجد عروض نشطة حالياً. تواصل معنا لمعرفة الأسعار وساعات اللعب المتاحة.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- سكشن المعرض -->
    <section id="gallery" class="gallery">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">معرض صور الصالة</h2>
                <p class="section-subtitle">شاهد أجواء اللعب وتصميم المكان المتميز</p>
            </div>

            <div class="gallery-grid">
                @forelse($gallery as $img)
                    <div class="gallery-item">
                        <img src="{{ tenant_asset($img) }}" alt="صورة الصالة">
                    </div>
                @empty
                    <!-- صور افتراضية في حال عدم رفع صور بعد -->
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1600861195091-690c92f1d2cc?auto=format&fit=crop&w=800&q=80" alt="قاعة اللعب">
                    </div>
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1606813907291-d86efa9b94db?auto=format&fit=crop&w=800&q=80" alt="بلاي ستيشن 5">
                    </div>
                    <div class="gallery-item">
                        <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=800&q=80" alt="أجواء اللعب">
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- سكشن التواصل والفوتر -->
    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                
                <div class="footer-col footer-about">
                    <h4>عن الصالة</h4>
                    <p>{{ $about }}</p>
                    <div class="social-links">
                        @if($facebook)
                            <a href="{{ $facebook }}" target="_blank" class="social-btn" title="Facebook">FB</a>
                        @endif
                        @if($instagram)
                            <a href="{{ $instagram }}" target="_blank" class="social-btn" title="Instagram">IG</a>
                        @endif
                    </div>
                </div>

                <div class="footer-col">
                    <h4>معلومات التواصل</h4>
                    <ul class="contact-info-list">
                        @if($address)
                            <li>
                                <span class="contact-icon">📍</span>
                                <span>العنوان: {{ $address }}</span>
                            </li>
                        @endif
                        @if($phone)
                            <li>
                                <span class="contact-icon">📞</span>
                                <span>اتصال مباشر: <a href="tel:{{ $phone }}" style="color:inherit; text-decoration:none; font-weight:700;">{{ $phone }}</a></span>
                            </li>
                        @endif
                        @if($whatsapp)
                            <li>
                                <span class="contact-icon">💬</span>
                                <span>واتساب: <a href="https://wa.me/{{ $whatsapp }}" target="_blank" style="color:inherit; text-decoration:none; font-weight:700;">{{ $whatsapp }}</a></span>
                            </li>
                        @endif
                    </ul>
                </div>

            </div>

            <div class="footer-copyright">
                &copy; {{ date('Y') }} {{ $title }}. جميع الحقوق محفوظة.
            </div>
        </div>
    </footer>

</body>
</html>
