<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Esalone منصة متكاملة لإدارة صالونات الحلاقة والكوافير ومراكز التجميل: مواعيد وخدمات وموظفون ومبيعات ومخزون وفروع.">
    <meta name="theme-color" content="#f7f3ed">
    <title>Esalone | إدارة متكاملة للصالونات ومراكز التجميل</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#f7f3ed;--surface:#fffdf9;--text:#302a25;--muted:#786f67;--line:#e9e1d8;--accent:#9a6c45;--accent-dark:#765035;--rose:#d8a6a0;--sage:#9eae9a;--shadow:0 28px 80px rgba(64,45,30,.12)}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth;scroll-padding-top:100px}
        body{margin:0;background:var(--bg);color:var(--text);font-family:"Alexandria",sans-serif;line-height:1.8}
        a{color:inherit;text-decoration:none}
        a:focus-visible,button:focus-visible{outline:3px solid #98704e;outline-offset:4px}
        .container{width:min(1180px,calc(100% - 40px));margin:auto}
        .skip-link{position:absolute;top:-60px;right:16px;z-index:100;background:var(--text);color:#fff;padding:9px 14px;border-radius:8px}
        .skip-link:focus{top:12px}
        .site-header{position:sticky;top:0;z-index:50;background:rgba(247,243,237,.92);backdrop-filter:blur(16px);border-bottom:1px solid rgba(75,55,38,.09)}
        .nav-inner{min-height:78px;display:flex;align-items:center;justify-content:space-between;gap:24px}
        .brand{display:inline-flex;align-items:center;gap:10px;font-size:1.22rem;font-weight:800;letter-spacing:-.04em;white-space:nowrap}
        .brand-mark{width:38px;height:38px;display:grid;place-items:center;border-radius:13px;background:var(--text);color:#f6eee3}
        .brand-mark svg{width:21px;height:21px}
        .nav-links,.header-actions{display:flex;align-items:center;gap:24px}
        .nav-links{color:var(--muted);font-size:.82rem}
        .nav-links a:hover{color:var(--accent-dark)}
        .header-actions{gap:10px}
        .button{min-height:46px;display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:0 19px;border:1px solid transparent;border-radius:12px;font-size:.8rem;font-weight:700;transition:transform .2s,background .2s}
        .button:hover{transform:translateY(-2px)}
        .button-primary{background:var(--text);color:#fffdf9}
        .button-primary:hover{background:var(--accent-dark)}
        .button-secondary{border-color:#d7cabe;background:rgba(255,255,255,.45)}
        .hero{position:relative;padding:82px 0 74px;overflow:hidden}
        .hero:before{content:"";position:absolute;width:600px;height:600px;top:-300px;left:-180px;border-radius:50%;background:radial-gradient(circle,rgba(216,166,160,.32),transparent 68%);pointer-events:none}
        .hero-grid{display:grid;grid-template-columns:.9fr 1.1fr;gap:64px;align-items:center;position:relative}
        .eyebrow{display:inline-flex;align-items:center;gap:9px;padding:7px 12px;border:1px solid #e3d5c8;border-radius:999px;background:rgba(255,255,255,.45);color:var(--accent-dark);font-size:.72rem;font-weight:700}
        .eyebrow-dot{width:7px;height:7px;border-radius:50%;background:#8c9d7c}
        h1,h2,h3,p{margin-top:0}
        .hero h1{max-width:660px;margin:20px 0;font-size:clamp(2.5rem,5vw,4.75rem);line-height:1.2;letter-spacing:-.06em}
        .hero h1 span{color:var(--accent)}
        .hero-copy>p{max-width:590px;margin-bottom:27px;color:var(--muted);font-size:.97rem}
        .hero-actions{display:flex;flex-wrap:wrap;gap:11px}
        .hero-proof{display:flex;flex-wrap:wrap;gap:22px;margin-top:35px;padding-top:23px;border-top:1px solid var(--line)}
        .proof-item{display:flex;align-items:center;gap:9px;color:var(--muted);font-size:.72rem}
        .proof-icon{width:29px;height:29px;display:grid;place-items:center;border-radius:9px;background:#eee7de;color:var(--accent-dark);font-size:.83rem}
        .product-preview{position:relative;padding:17px;border:1px solid rgba(129,96,68,.15);border-radius:26px;background:rgba(255,253,249,.83);box-shadow:var(--shadow);transform:rotate(-1deg)}
        .preview-top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:2px 2px 15px}
        .preview-title{display:flex;align-items:center;gap:10px;font-size:.77rem;font-weight:700}
        .preview-title-icon{width:33px;height:33px;display:grid;place-items:center;border-radius:11px;background:#eee5db;color:var(--accent-dark)}
        .preview-title small,.preview-date{display:block;color:var(--muted);font-size:.62rem;font-weight:500}
        .preview-date{padding:6px 10px;border:1px solid var(--line);border-radius:9px}
        .preview-content{display:grid;grid-template-columns:.82fr 1.18fr;gap:12px;direction:rtl}
        .preview-sidebar{display:grid;align-content:start;gap:9px;padding:13px;border:1px solid var(--line);border-radius:15px;background:#faf7f2}
        .sidebar-label{margin-bottom:2px;color:var(--muted);font-size:.62rem}
        .staff-item{display:flex;align-items:center;gap:8px;padding:7px;border-radius:10px;background:#fffdf9;font-size:.65rem}
        .staff-avatar{width:26px;height:26px;display:grid;place-items:center;border-radius:9px;background:#ead8d0;color:#75594e;font-size:.63rem;font-weight:700}
        .staff-item:nth-of-type(3) .staff-avatar{background:#e1e8dc;color:#61715a}
        .staff-item small{display:block;color:var(--muted);font-size:.55rem}
        .staff-status{width:6px;height:6px;margin-right:auto;border-radius:50%;background:#7b9c70}
        .schedule{padding:13px;border:1px solid var(--line);border-radius:15px;background:#fffdf9}
        .schedule-heading{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:.66rem;font-weight:700}
        .schedule-heading span{color:var(--muted);font-size:.57rem;font-weight:500}
        .appointment{display:grid;grid-template-columns:45px 1fr auto;align-items:center;gap:8px;margin-top:7px;padding:9px;border-radius:10px;background:#f7f1ea;border-right:3px solid var(--accent)}
        .appointment:nth-of-type(3){background:#f3eeea;border-color:#c78985}
        .appointment:nth-of-type(4){background:#eff2eb;border-color:#91a286}
        .appointment time{font-size:.57rem;font-weight:700;direction:ltr}
        .appointment strong{display:block;font-size:.61rem}
        .appointment small{display:block;color:var(--muted);font-size:.53rem}
        .appointment-status{padding:3px 6px;border-radius:7px;background:#e4ebdf;color:#596d50;font-size:.5rem;white-space:nowrap}
        .preview-footer{display:flex;justify-content:space-between;gap:8px;margin-top:12px}
        .metric{flex:1;padding:10px 12px;border:1px solid var(--line);border-radius:12px;background:#fffdf9}
        .metric small{display:block;color:var(--muted);font-size:.55rem}
        .metric strong{font-size:.75rem}
        .preview-caption{position:absolute;left:-20px;bottom:35px;padding:11px 15px;border:1px solid #e6dbce;border-radius:12px;background:#fffdf9;box-shadow:0 12px 32px rgba(64,45,30,.1);font-size:.64rem;font-weight:700}
        .preview-caption span{display:block;color:var(--muted);font-size:.55rem;font-weight:500}
        .section{padding:86px 0}
        .section-soft{background:#efe9e0;border-block:1px solid #e7ded3}
        .section-heading{max-width:730px;margin-bottom:37px}
        .section-heading h2{margin-bottom:12px;font-size:clamp(1.85rem,3.5vw,3rem);line-height:1.3;letter-spacing:-.045em}
        .section-heading p{max-width:600px;margin-bottom:0;color:var(--muted);font-size:.86rem}
        .workflow-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
        .workflow-card{overflow:hidden;border:1px solid #e3d9ce;border-radius:18px;background:var(--surface)}
        .image-zoom-trigger{display:block;width:100%;padding:10px;border:0;background:#f7f3ed;cursor:zoom-in}
        .image-zoom-trigger:focus-visible{position:relative;z-index:1;outline-offset:-4px}
        .workflow-image{display:block;width:100%;aspect-ratio:1.35;object-fit:contain}
        .workflow-copy{padding:18px 20px 22px}
        .workflow-number{display:grid;place-items:center;width:32px;height:32px;margin-bottom:13px;border-radius:10px;background:#eee5db;color:var(--accent-dark);font-size:.72rem;font-weight:800}
        .workflow-card h3{margin-bottom:8px;font-size:.91rem}
        .workflow-card p{margin-bottom:0;color:var(--muted);font-size:.72rem}
        .feature-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        .feature-card{padding:24px;border:1px solid var(--line);border-radius:17px;background:var(--surface)}
        .feature-icon{width:42px;height:42px;display:grid;place-items:center;margin-bottom:17px;border-radius:13px;background:#f0e7dc;color:var(--accent-dark);font-size:1.08rem}
        .feature-card h3{margin-bottom:8px;font-size:.93rem}
        .feature-card p{margin-bottom:0;color:var(--muted);font-size:.73rem}
        .gallery-section{background:#fffdf9;border-block:1px solid var(--line)}
        .gallery-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
        .gallery-card{overflow:hidden;margin:0;border:1px solid #e8dfd5;border-radius:18px;background:var(--bg);box-shadow:0 12px 35px rgba(64,45,30,.06)}
        .gallery-card .image-zoom-trigger{padding:8px}
        .gallery-card img{display:block;width:100%;aspect-ratio:1.25;object-fit:contain;transition:transform .25s}
        .gallery-card .image-zoom-trigger:hover img{transform:scale(1.03)}
        .gallery-card figcaption{display:grid;gap:4px;padding:12px 14px;font-size:.76rem;font-weight:700}
        .gallery-card figcaption span{color:var(--muted);font-size:.64rem;font-weight:500}
        .image-lightbox{width:min(94vw,1100px);max-width:none;max-height:92dvh;padding:0;overflow:visible;border:1px solid #e8dfd5;border-radius:18px;background:#fffdf9;color:var(--text);box-shadow:0 24px 80px rgba(30,23,18,.35)}
        .image-lightbox::backdrop{background:rgba(28,23,19,.78);backdrop-filter:blur(5px)}
        .lightbox-content{position:relative;display:grid;justify-items:center;gap:12px;padding:16px}
        .lightbox-image{display:block;max-width:100%;max-height:78dvh;object-fit:contain}
        .lightbox-caption{max-width:90%;color:var(--muted);font-size:.82rem;text-align:center}
        .lightbox-close{position:absolute;top:8px;right:8px;z-index:1;display:grid;width:38px;height:38px;place-items:center;border:1px solid var(--line);border-radius:50%;background:#fffdf9;color:var(--text);font:inherit;font-size:1.35rem;cursor:pointer}
        .branch-panel{display:grid;grid-template-columns:.8fr 1.2fr;gap:34px;align-items:center;padding:34px;border:1px solid #e2d7cb;border-radius:23px;background:linear-gradient(130deg,#fffdf9,#f1e9df)}
        .branch-panel h2{margin-bottom:12px;font-size:clamp(1.65rem,3vw,2.5rem);line-height:1.35}
        .branch-panel p{margin-bottom:0;color:var(--muted);font-size:.8rem}
        .branch-features{display:grid;grid-template-columns:1fr 1fr;gap:10px}
        .branch-feature{display:flex;align-items:center;gap:10px;padding:14px;border:1px solid #e9e0d6;border-radius:12px;background:rgba(255,253,249,.8);font-size:.69rem;font-weight:600}
        .branch-feature span{color:#8c9d7c;font-size:1rem}
        .cta{padding:0 0 88px}
        .cta-box{display:flex;justify-content:space-between;align-items:center;gap:26px;padding:36px;border-radius:22px;background:#39302a;color:#fffaf3}
        .cta-box h2{margin-bottom:9px;font-size:clamp(1.55rem,3vw,2.35rem);line-height:1.35}
        .cta-box p{max-width:620px;margin-bottom:0;color:#ded3c7;font-size:.77rem}
        .cta-actions{display:flex;flex-wrap:wrap;gap:9px;flex-shrink:0}
        .cta .button-primary{background:#f4e6d6;color:#3e3027}
        .cta .button-secondary{border-color:rgba(255,255,255,.28);color:#fffaf3;background:transparent}
        .site-footer{padding:25px 0;border-top:1px solid var(--line)}
        .footer-inner{display:flex;align-items:center;justify-content:space-between;gap:20px;color:var(--muted);font-size:.68rem}
        .footer-links{display:flex;gap:18px}
        @media(max-width:980px){.hero-grid{grid-template-columns:1fr;gap:44px}.hero-copy{max-width:760px}.product-preview{max-width:760px;width:100%;margin:auto}.workflow-grid{grid-template-columns:repeat(2,1fr)}.gallery-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.branch-panel{grid-template-columns:1fr}.cta-box{align-items:flex-start;flex-direction:column}}
        @media(max-width:700px){html{scroll-padding-top:125px}.nav-inner{min-height:unset;padding:12px 0;flex-wrap:wrap;gap:9px}.brand{font-size:1.05rem}.brand-mark{width:34px;height:34px}.header-actions{margin-right:auto}.header-actions .button{min-height:38px;padding:0 11px;font-size:.66rem}.nav-links{order:3;width:100%;gap:19px;overflow-x:auto;padding:5px 0 8px;white-space:nowrap;font-size:.72rem}.hero{padding:53px 0 55px}.hero-grid{gap:34px}.hero h1{font-size:clamp(2.25rem,10vw,3.3rem)}.hero-copy>p{font-size:.86rem}.hero-proof{gap:12px}.proof-item{font-size:.63rem}.product-preview{padding:11px;border-radius:19px}.preview-content{grid-template-columns:.76fr 1.24fr;gap:7px}.preview-sidebar,.schedule{padding:9px}.appointment{grid-template-columns:39px 1fr;gap:5px;padding:7px}.appointment-status{display:none}.preview-caption{left:-6px;bottom:18px}.section{padding:62px 0}.workflow-grid,.feature-grid{grid-template-columns:1fr 1fr}.section-heading{margin-bottom:26px}.branch-panel{padding:23px}.cta{padding-bottom:62px}.cta-box{padding:27px 22px}.footer-inner{flex-wrap:wrap}.footer-links{order:3;width:100%;justify-content:center}}
        @media(max-width:430px){.container{width:min(100% - 26px,1180px)}.hero-actions .button{flex:1;padding:0 12px;font-size:.72rem}.hero-proof{display:grid;grid-template-columns:1fr 1fr}.preview-sidebar{display:none}.preview-content{grid-template-columns:1fr}.preview-date{font-size:.52rem}.workflow-grid,.feature-grid,.branch-features{grid-template-columns:1fr}.cta-actions{width:100%}.cta-actions .button{flex:1}.footer-inner{justify-content:center;text-align:center}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}}
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">تجاوز إلى المحتوى</a>
    <header class="site-header">
        <div class="container nav-inner">
            <a class="brand" href="#top" aria-label="Esalone - الصفحة الرئيسية">
                <span class="brand-mark" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none"><path d="M7 7.5 17 17M17 7.5 7 17M5.5 5.5l2 2m9-2 2 2m-13 9 2 2m9-2 2 2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="5.5" cy="5.5" r="2.2" stroke="currentColor" stroke-width="1.5"/><circle cx="18.5" cy="5.5" r="2.2" stroke="currentColor" stroke-width="1.5"/><circle cx="5.5" cy="18.5" r="2.2" stroke="currentColor" stroke-width="1.5"/><circle cx="18.5" cy="18.5" r="2.2" stroke="currentColor" stroke-width="1.5"/></svg></span>
                Esalone
            </a>
            <nav class="nav-links" aria-label="التنقل الرئيسي">
                <a href="#platform">المنصة</a><a href="#workflow">طريقة العمل</a><a href="#features">المميزات</a><a href="#gallery">معرض الواجهات</a>
            </nav>
            <div class="header-actions">
                <a class="button button-primary" href="https://etech-valley.com/customer">طلب الاشتراك</a>
            </div>
        </div>
    </header>

    <main id="main-content">
        <section class="hero" id="top">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> منصة إدارة صالونات الحلاقة والتجميل</span>
                    <h1>كل تفاصيل صالونك، <span>مرتبة في مكان واحد</span></h1>
                    <p>من تنظيم المواعيد وتوزيع الخدمات على فريقك، إلى البيع والمخزون وإغلاق الوردية. Esalone يساعدك تدير شغلك اليومي بوضوح من أول عميل لآخر فاتورة.</p>
                    <div class="hero-actions">
                        <a class="button button-primary" href="https://etech-valley.com/customer">اطلب اشتراك Esalone</a>
                        <a class="button button-secondary" href="#workflow">اكتشف طريقة العمل</a>
                    </div>
                    <div class="hero-proof" aria-label="أهم وظائف النظام">
                        <div class="proof-item"><span class="proof-icon" aria-hidden="true">◷</span> مواعيد وخدمات</div>
                        <div class="proof-item"><span class="proof-icon" aria-hidden="true">♙</span> فريق وفروع</div>
                        <div class="proof-item"><span class="proof-icon" aria-hidden="true">▤</span> مبيعات ومخزون</div>
                    </div>
                </div>
                <div class="product-preview" id="platform" aria-label="تصوّر توضيحي لجدول مواعيد الصالون">
                    <div class="preview-top">
                        <div class="preview-title"><span class="preview-title-icon" aria-hidden="true">◷</span><div>جدول المواعيد<small>متابعة يوم العمل</small></div></div>
                        <span class="preview-date">الثلاثاء، 20 أكتوبر</span>
                    </div>
                    <div class="preview-content">
                        <aside class="preview-sidebar">
                            <span class="sidebar-label">فريق العمل</span>
                            <div class="staff-item"><span class="staff-avatar">م</span><span>منى<small>خبيرة شعر</small></span><i class="staff-status"></i></div>
                            <div class="staff-item"><span class="staff-avatar">س</span><span>سارة<small>أخصائية تجميل</small></span><i class="staff-status"></i></div>
                            <div class="staff-item"><span class="staff-avatar">أ</span><span>أحمد<small>حلاق</small></span><i class="staff-status"></i></div>
                        </aside>
                        <div class="schedule">
                            <div class="schedule-heading">مواعيد اليوم <span>عرض توضيحي</span></div>
                            <div class="appointment"><time>10:00</time><div><strong>قص وتصفيف</strong><small>منى · 45 دقيقة</small></div><span class="appointment-status">مؤكد</span></div>
                            <div class="appointment"><time>11:30</time><div><strong>عناية بالبشرة</strong><small>سارة · 60 دقيقة</small></div><span class="appointment-status">قادم</span></div>
                            <div class="appointment"><time>12:15</time><div><strong>قص شعر</strong><small>أحمد · 30 دقيقة</small></div><span class="appointment-status">قادم</span></div>
                        </div>
                    </div>
                    <div class="preview-footer">
                        <div class="metric"><small>مواعيد اليوم</small><strong>مرتبة حسب الفريق</strong></div>
                        <div class="metric"><small>متابعة أسهل</small><strong>حالة كل موعد واضحة</strong></div>
                    </div>
                    <div class="preview-caption">واجهة توضيحية للمنصة<span>تُدار الخدمات والمواعيد من لوحة الصالون</span></div>
                </div>
            </div>
        </section>

        <section class="section section-soft" id="workflow">
            <div class="container">
                <div class="section-heading"><h2>رحلة العميل من الحجز إلى التحصيل</h2><p>خطوات العمل مترابطة لتسهيل متابعة الفريق، وتسجيل الخدمة، ومعرفة نتيجة كل وردية.</p></div>
                <div class="workflow-grid">
                    <article class="workflow-card"><button class="image-zoom-trigger" type="button" aria-label="عرض تصميم جدول مواعيد الصالون بحجم أكبر" data-caption="جدول مواعيد الصالون"><img class="workflow-image" src="{{ asset('images/esalone-appointments.svg') }}" alt="تصميم توضيحي لجدول مواعيد الصالون" loading="lazy" width="960" height="560"></button><div class="workflow-copy"><span class="workflow-number">01</span><h3>نظّم المواعيد</h3><p>سجّل الموعد والخدمات المطلوبة والفرع والموظف، وتابع حالته في لوحة واحدة.</p></div></article>
                    <article class="workflow-card"><button class="image-zoom-trigger" type="button" aria-label="عرض تصميم إدارة الخدمات والفريق بحجم أكبر" data-caption="الخدمات وفريق العمل"><img class="workflow-image" src="{{ asset('images/esalone-services.svg') }}" alt="تصميم توضيحي لإدارة الخدمات وفريق العمل" loading="lazy" width="960" height="560"></button><div class="workflow-copy"><span class="workflow-number">02</span><h3>وزّع الخدمات على فريقك</h3><p>عرّف الخدمات وأسعارها ومددها، واربط كل موعد بالموظف المناسب.</p></div></article>
                    <article class="workflow-card"><button class="image-zoom-trigger" type="button" aria-label="عرض تصميم فاتورة الخدمة ومنتجات العناية بحجم أكبر" data-caption="الفاتورة ونقطة البيع"><img class="workflow-image" src="{{ asset('images/esalone-checkout.svg') }}" alt="تصميم توضيحي لفاتورة الخدمة ومنتجات العناية" loading="lazy" width="960" height="560"></button><div class="workflow-copy"><span class="workflow-number">03</span><h3>حصّل الخدمة والمنتجات</h3><p>أنشئ فاتورة للموعد المكتمل وأضف منتجات العناية من نقطة البيع.</p></div></article>
                    <article class="workflow-card"><button class="image-zoom-trigger" type="button" aria-label="عرض تصميم متابعة الفروع والورديات بحجم أكبر" data-caption="متابعة الفروع والورديات"><img class="workflow-image" src="{{ asset('images/esalone-dashboard.svg') }}" alt="تصميم توضيحي لتقارير الفروع والورديات والمخزون" loading="lazy" width="960" height="560"></button><div class="workflow-copy"><span class="workflow-number">04</span><h3>راجع الوردية والفروع</h3><p>طابق المبيعات والمصروفات وحركة المخزون قبل تسليم الوردية.</p></div></article>
                </div>
            </div>
        </section>

        <section class="section gallery-section" id="gallery">
            <div class="container">
                <div class="section-heading"><h2>نظرة أقرب على واجهات Esalone</h2><p>تصاميم توضيحية للواجهات والوظائف المتاحة لإدارة الصالون، من جدول المواعيد حتى متابعة الفروع.</p></div>
                <div class="gallery-grid">
                    <figure class="gallery-card"><button class="image-zoom-trigger" type="button" aria-label="تكبير صورة جدول مواعيد Esalone" data-caption="جدول المواعيد — تنظيم يوم العمل والفريق"><img src="{{ asset('images/esalone-appointments.svg') }}" alt="واجهة جدول مواعيد Esalone، تصميم توضيحي" loading="lazy" width="960" height="560"></button><figcaption>جدول المواعيد <span>تنظيم يوم العمل والفريق</span></figcaption></figure>
                    <figure class="gallery-card"><button class="image-zoom-trigger" type="button" aria-label="تكبير صورة الخدمات وفريق العمل في Esalone" data-caption="الخدمات وفريق العمل — إدارة الأسعار والمدد والتخصصات"><img src="{{ asset('images/esalone-services.svg') }}" alt="واجهة قائمة الخدمات والفريق في Esalone، تصميم توضيحي" loading="lazy" width="960" height="560"></button><figcaption>الخدمات وفريق العمل <span>إدارة الأسعار والمدد والتخصصات</span></figcaption></figure>
                    <figure class="gallery-card"><button class="image-zoom-trigger" type="button" aria-label="تكبير صورة الفاتورة ونقطة البيع في Esalone" data-caption="الفاتورة ونقطة البيع — الخدمات والمنتجات في حساب واحد"><img src="{{ asset('images/esalone-checkout.svg') }}" alt="واجهة نقطة البيع وتحصيل الخدمة في Esalone، تصميم توضيحي" loading="lazy" width="960" height="560"></button><figcaption>الفاتورة ونقطة البيع <span>الخدمات والمنتجات في حساب واحد</span></figcaption></figure>
                    <figure class="gallery-card"><button class="image-zoom-trigger" type="button" aria-label="تكبير صورة متابعة الفروع والورديات في Esalone" data-caption="متابعة الفروع والورديات — ملخص تشغيلي واضح لكل فرع"><img src="{{ asset('images/esalone-dashboard.svg') }}" alt="واجهة ملخص الفروع والورديات في Esalone، تصميم توضيحي" loading="lazy" width="960" height="560"></button><figcaption>متابعة الفروع والورديات <span>ملخص تشغيلي واضح لكل فرع</span></figcaption></figure>
                </div>
            </div>
        </section>

        <section class="section" id="features">
            <div class="container">
                <div class="section-heading"><h2>إدارة تناسب طبيعة شغلك</h2><p>الوظائف الأساسية التي يحتاجها صالون الحلاقة أو الكوافير أو مركز التجميل، بدون أدوات تشغيل لا تخص نشاطك.</p></div>
                <div class="feature-grid">
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">◷</span><h3>مواعيد بلا تعارض</h3><p>تابع حالة المواعيد ومدد الخدمات، مع التحقق من عدم تداخل مواعيد الموظف.</p></article>
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">✂</span><h3>الخدمات وفريق العمل</h3><p>نظّم قائمة الخدمات والأسعار والمدد، وملفات الموظفين حسب كل فرع.</p></article>
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">▤</span><h3>نقطة بيع للصالون</h3><p>حصّل خدماتك وبِع مستحضرات العناية في فاتورة واحدة مع متابعة الإيراد.</p></article>
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">▧</span><h3>مخزون المنتجات</h3><p>تابع مستحضرات البيع والمستهلكات وحركة المخزون والتنبيهات المرتبطة بها.</p></article>
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">♙</span><h3>ملفات العملاء والولاء</h3><p>احتفظ بسجل العميل ومشترياته واربطه بنقاط الولاء والمحفظة.</p></article>
                    <article class="feature-card"><span class="feature-icon" aria-hidden="true">⌂</span><h3>الفروع والتقارير</h3><p>تابع كل فرع بصلاحياته ووردياته ومبيعاته من مساحة عمل واحدة.</p></article>
                </div>
            </div>
        </section>

        <section class="section" id="branches">
            <div class="container branch-panel">
                <div><span class="eyebrow">لصالون مستقل أو علامة متعددة الفروع</span><h2>كبر شغلك، وخلي إدارتك متناسقة</h2><p>افصل الخدمات والموظفين والورديات حسب الفرع، واحتفظ برؤية مركزية تساعدك تتابع الأداء بدون خلط بيانات الفروع.</p></div>
                <div class="branch-features">
                    <div class="branch-feature"><span aria-hidden="true">✓</span> خدمات وموظفون لكل فرع</div>
                    <div class="branch-feature"><span aria-hidden="true">✓</span> متابعة المواعيد والتحصيل</div>
                    <div class="branch-feature"><span aria-hidden="true">✓</span> صلاحيات تشغيل واضحة</div>
                    <div class="branch-feature"><span aria-hidden="true">✓</span> تقارير ومخزون منظم</div>
                </div>
            </div>
        </section>

        <section class="cta" id="contact">
            <div class="container cta-box">
                <div><h2>ابدأ بتنظيم يوم صالونك</h2><p>اجمع المواعيد والخدمات والفريق والمبيعات في نظام واحد، وخلي تفاصيل التشغيل أوضح لك ولطاقمك.</p></div>
                <div class="cta-actions"><a class="button button-primary" href="https://etech-valley.com/customer">اطلب الاشتراك عبر منصة الحسابات</a><a class="button button-secondary" href="https://wa.me/201558787680">تواصل معنا</a></div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner"><a class="brand" href="#top"><span class="brand-mark" aria-hidden="true">E</span>Esalone</a><nav class="footer-links" aria-label="روابط أسفل الصفحة"><a href="#workflow">طريقة العمل</a><a href="#features">المميزات</a><a href="#contact">تواصل معنا</a></nav><span>© {{ date('Y') }} جميع الحقوق محفوظة</span></div>
    </footer>
    <dialog class="image-lightbox" id="image-lightbox" aria-label="عارض صور واجهات Esalone">
        <div class="lightbox-content">
            <button class="lightbox-close" type="button" aria-label="إغلاق الصورة">&times;</button>
            <img class="lightbox-image" alt="">
            <p class="lightbox-caption"></p>
        </div>
    </dialog>
    <script>
        const imageLightbox = document.getElementById('image-lightbox');
        const lightboxImage = imageLightbox.querySelector('.lightbox-image');
        const lightboxCaption = imageLightbox.querySelector('.lightbox-caption');

        document.querySelectorAll('.image-zoom-trigger').forEach((trigger) => {
            trigger.addEventListener('click', () => {
                const thumbnail = trigger.querySelector('img');

                lightboxImage.src = thumbnail.src;
                lightboxImage.alt = thumbnail.alt;
                lightboxCaption.textContent = trigger.dataset.caption;
                imageLightbox.showModal();
            });
        });

        imageLightbox.querySelector('.lightbox-close').addEventListener('click', () => {
            imageLightbox.close();
        });

        imageLightbox.addEventListener('click', (event) => {
            if (event.target === imageLightbox) {
                imageLightbox.close();
            }
        });
    </script>
</body>
</html>
