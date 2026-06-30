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
            --bg-color: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.7);
            --primary: #8b5cf6;
            --primary-glow: rgba(139, 92, 246, 0.4);
            --secondary: #3b82f6;
            --secondary-glow: rgba(59, 130, 246, 0.4);
            --accent: #10b981;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(255, 255, 255, 0.08);
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
                radial-gradient(circle at 10% 20%, rgba(139, 92, 246, 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(59, 130, 246, 0.15) 0%, transparent 40%);
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
            padding: 20px 0;
            backdrop-filter: blur(12px);
            background-color: rgba(11, 15, 25, 0.8);
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
            font-size: 1.6rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 8px;
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
            font-size: 0.95rem;
            font-weight: 700;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--primary);
        }

        .btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            padding: 10px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px var(--primary-glow);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 92, 246, 0.6);
        }

        /* الهيرو سكشن */
        .hero {
            padding: 100px 0 80px;
            text-align: center;
            position: relative;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.2;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 40%, #e2e8f0 70%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-desc {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 700px;
            margin: 0 auto 35px;
            line-height: 1.8;
            font-weight: 500;
        }

        .hero-btns {
            display: flex;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            box-shadow: none;
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--text-muted);
            box-shadow: none;
        }

        /* سكشن المميزات والأجهزة */
        .features {
            padding: 80px 0;
            position: relative;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 12px;
        }

        .section-subtitle {
            color: var(--text-muted);
            font-size: 1.05rem;
            font-weight: 500;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }

        .feature-card {
            background-color: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.1);
        }

        .feature-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(59, 130, 246, 0.2));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 20px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .feature-card p {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.6;
        }

        /* سكشن العروض */
        .offers {
            padding: 80px 0;
            background-color: rgba(17, 24, 39, 0.3);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }

        .offers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .offer-card {
            background-color: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            overflow: hidden;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .offer-card:hover {
            transform: translateY(-5px);
            border-color: var(--secondary);
            box-shadow: 0 15px 35px rgba(59, 130, 246, 0.15);
        }

        .offer-header {
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .offer-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 4px 12px;
            border-radius: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            display: inline-block;
            margin-bottom: 12px;
        }

        .offer-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
        }

        .offer-price {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--accent);
            display: flex;
            align-items: baseline;
            gap: 4px;
            margin-top: 10px;
        }

        .offer-price span {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        .offer-body {
            padding: 24px;
            flex-grow: 1;
        }

        .offer-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 0.9rem;
            color: var(--text-main);
        }

        .offer-info-icon {
            color: var(--secondary);
            width: 18px;
            height: 18px;
        }

        .offer-remark {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 12px;
            font-size: 0.85rem;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
            margin-top: 15px;
        }

        .offer-footer {
            padding: 24px;
            border-top: 1px solid var(--border-color);
            background: rgba(255, 255, 255, 0.01);
        }

        /* سكشن معرض الصور */
        .gallery {
            padding: 80px 0;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .gallery-item {
            height: 220px;
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid var(--border-color);
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gallery-item:hover {
            transform: scale(1.03);
            border-color: var(--primary);
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* سكشن التواصل والفوتر */
        footer {
            background-color: #060911;
            padding: 60px 0 30px;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-col h4 {
            font-size: 1.1rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 8px;
        }

        .footer-col h4::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 40px;
            height: 2px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
        }

        .footer-about p {
            color: var(--text-muted);
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .social-links {
            display: flex;
            gap: 12px;
        }

        .social-btn {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
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
            gap: 12px;
            margin-bottom: 14px;
            color: var(--text-muted);
        }

        .contact-icon {
            color: var(--primary);
        }

        .footer-copyright {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid var(--border-color);
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        /* استجابة الشاشات الصغيرة */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.3rem;
            }
            .hero {
                padding: 60px 0 50px;
            }
            nav {
                display: none; /* إخفاء المنيو على الشاشات الصغيرة للتبسيط */
            }
        }
    </style>
</head>
<body>

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
                    <li><a href="#gallery">المعرض</a></li>
                    <li><a href="#contact">اتصل بنا</a></li>
                </ul>
            </nav>
            <div>
                @if($whatsapp)
                    <a href="https://wa.me/{{ $whatsapp }}" target="_blank" class="btn">
                        تواصل معنا
                    </a>
                @elseif($phone)
                    <a href="tel:{{ $phone }}" class="btn">
                        تواصل معنا
                    </a>
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
                <a href="#offers" class="btn">اكتشف العروض القوية</a>
                <a href="#contact" class="btn btn-outline">تواصل معنا واحجز الآن</a>
            </div>
        </div>
    </section>

    <!-- سكشن المميزات والخدمات -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">خدمات الصالة وتجهيزاتها</h2>
                <p class="section-subtitle">نوفر لك أرقى تجربة لعب غامرة وتجهيزات على أعلى مستوى</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🎮</div>
                    <h3>أجهزة الجيل الجديد</h3>
                    <p>أجهزة PlayStation 5 حديثة بالكامل مع أحدث الألعاب الحصرية وإمكانية اللعب الفردي والجماعي.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📺</div>
                    <h3>شاشات فائقة الدقة 4K</h3>
                    <p>شاشات عملاقة تدعم تقنية HDR ومعدل تحديث عالي لضمان تجربة بصرية سلسلة وسريعة الاستجابة.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛋️</div>
                    <h3>مقاعد مخصصة ومريحة</h3>
                    <p>مقاعد مريحة للغاية ومسافات كافية بين الغرف لضمان راحتك وخصوصيتك أثناء اللعب لساعات طويلة.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🍹</div>
                    <h3>مشروبات ووجبات خفيفة</h3>
                    <p>قائمة مميزة من المشروبات الساخنة والباردة والوجبات الخفيفة التي تقدم لك مباشرة أثناء اللعب.</p>
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
                                <span>مدة العرض: <strong>{{ $offer->period }} دقيقة</strong></span>
                            </div>
                            @if($offer->time_from && $offer->time_to)
                                <div class="offer-info-item">
                                    🕒 
                                    <span>متاح من: <strong>{{ \Carbon\Carbon::parse($offer->time_from)->format('h:i A') }}</strong> إلى: <strong>{{ \Carbon\Carbon::parse($offer->time_to)->format('h:i A') }}</strong></span>
                                </div>
                            @endif
                            @if($offer->devices)
                                <div class="offer-info-item">
                                    🎮 
                                    <span>الأجهزة المشمولة: {{ $offer->devices }}</span>
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
                    <!-- صور افتراضية ممتازة في حال عدم رفع صور بعد -->
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
