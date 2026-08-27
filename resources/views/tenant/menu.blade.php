<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة المأكولات والمشروبات - {{ $title }}</title>
    
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
            --accent: #10b981;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: rgba(255, 255, 255, 0.06);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', sans-serif;
            scroll-behavior: smooth;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 5% 15%, rgba(139, 92, 246, 0.15) 0%, transparent 45%),
                radial-gradient(circle at 95% 85%, rgba(59, 130, 246, 0.15) 0%, transparent 45%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            width: 100%;
        }

        /* الهيدر */
        header {
            padding: 20px 0;
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
            font-size: 1.4rem;
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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

        .btn-back {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--text-muted);
            transform: translateX(-3px);
        }

        /* عنوان الصفحة والكاتيجوريز */
        .menu-title-section {
            padding: 40px 0 20px;
            text-align: center;
        }

        .menu-title {
            font-size: 2.2rem;
            font-weight: 900;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 50%, var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
        }

        /* تصنيفات المنيو التبويبية */
        .categories-nav {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 24px 0 40px;
            overflow-x: auto;
            padding-bottom: 8px;
        }

        .category-tab {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 20px;
            border-radius: 9999px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .category-tab:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--primary);
        }

        /* الأقسام والمنتجات */
        .menu-section {
            margin-bottom: 60px;
            scroll-margin-top: 100px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            position: relative;
        }

        .section-badge {
            font-size: 0.72rem;
            background: rgba(139, 92, 246, 0.12);
            color: var(--primary);
            border: 1px solid rgba(139, 92, 246, 0.25);
            padding: 3px 10px;
            border-radius: 9999px;
            font-weight: 700;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 12px 30px rgba(139, 92, 246, 0.12);
        }

        .product-img-wrapper {
            height: 180px;
            width: 100%;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.01);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .product-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-no-img {
            color: var(--text-muted);
            font-size: 2.2rem;
            opacity: 0.25;
        }

        .product-info {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .product-name {
            font-size: 1.05rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 6px;
        }

        .product-desc {
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 16px;
            flex-grow: 1;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid rgba(255,255,255,0.03);
            padding-top: 12px;
        }

        .product-price {
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--accent);
            display: flex;
            align-items: baseline;
            gap: 2px;
        }

        .product-price span {
            font-size: 0.72rem;
            color: var(--text-muted);
            font-weight: 500;
        }

        .empty-menu {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        /* فوتر */
        footer {
            margin-top: auto;
            padding: 30px 0;
            border-top: 1px solid var(--border-color);
            background: #04060b;
            text-align: center;
            font-size: 0.8rem;
            color: var(--text-muted);
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
            <a href="/" class="btn-back">
                <span>العودة للرئيسية</span>
                <span>←</span>
            </a>
        </div>
    </header>

    <!-- العنوان الترحيبي والتبويبات -->
    <div class="container">
        <section class="menu-title-section">
            <h1 class="menu-title">قائمة المأكولات والمشروبات</h1>
            <p class="menu-subtitle">استمتع بتشكيلة رائعة من المشروبات الباردة والساخنة والمأكولات الخفيفة أثناء اللعب</p>

            @if(isset($branches) && $branches->count() > 1)
                <form method="GET" action="{{ route('tenant.menu') }}" class="branch-select-form" style="margin-top: 18px; display: inline-flex; align-items: center; gap: 10px; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); padding: 6px 16px; border-radius: 9999px;">
                    <label for="branch_id" style="font-size: 0.88rem; color: var(--text-muted); font-weight: 700;">عرض أسعار فرع:</label>
                    <select name="branch_id" id="branch_id" onchange="this.form.submit()" style="background: rgba(13, 20, 35, 0.95); color: #fff; border: 1px solid var(--primary); padding: 6px 14px; border-radius: 10px; font-weight: 700; font-size: 0.88rem; cursor: pointer; outline: none;">
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ ($selectedBranchId ?? null) == $branch->id ? 'selected' : '' }}>
                                📍 {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
            @endif
        </section>

        @if($groups->isNotEmpty())
            <!-- شريط التنقل السريع بين الأقسام -->
            <div class="categories-nav">
                @foreach($groups as $group)
                    @if($group->items->isNotEmpty())
                        <a href="#group-{{ $group->id }}" class="category-tab">{{ $group->name }}</a>
                    @endif
                @endforeach
            </div>

            <!-- عرض المنتجات حسب كل قسم -->
            @foreach($groups as $group)
                @if($group->items->isNotEmpty())
                    <section id="group-{{ $group->id }}" class="menu-section">
                        <div class="section-header">
                            <h2 class="section-title">{{ $group->name }}</h2>
                            <span class="section-badge">{{ $group->items->count() }} صنف</span>
                        </div>

                        <div class="products-grid">
                            @foreach($group->items as $item)
                                <div class="product-card">
                                    <div class="product-img-wrapper">
                                        @if($item->picture)
                                            <img src="{{ tenant_asset($item->picture) }}" alt="{{ $item->name }}" />
                                        @else
                                            <div class="product-no-img">🍔</div>
                                        @endif
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">{{ $item->name }}</h3>
                                        <p class="product-desc">{{ $item->description ?: 'صنف طازج ومميز مجهز خصيصاً لك لتعزيز متعة اللعب.' }}</p>
                                        <div class="product-footer">
                                            <div class="product-price">
                                                {{ number_format($item->effective_price ?? $item->sale_price, 2) }} <span>ج.م</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endforeach
        @else
            <div class="empty-menu">
                <p>⚠️ لا توجد أصناف مضافة في المنيو حالياً.</p>
            </div>
        @endif
    </div>

    <!-- فوتر -->
    <footer>
        <div class="container">
            <p>جميع الحقوق محفوظة © {{ date('Y') }} - {{ $title }}</p>
        </div>
    </footer>

</body>
</html>
