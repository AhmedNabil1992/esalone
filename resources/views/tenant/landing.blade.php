<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="{{ $shopName }}">
    <meta property="og:description" content="{{ $description }}">
    <title>{{ $shopName }} | صالون حلاقة وتجميل</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @php
        $palettes = [
            'neon' => ['accent' => '#76579c', 'accent2' => '#ad93c7', 'background' => '#f6f2fa', 'surface' => '#fffcff', 'text' => '#342b3b', 'glow' => '118,87,156'],
            'ember' => ['accent' => '#9a5638', 'accent2' => '#d99b78', 'background' => '#fbf2ea', 'surface' => '#fff9f4', 'text' => '#382a22', 'glow' => '154,86,56'],
            'electric' => ['accent' => '#356a83', 'accent2' => '#77afc0', 'background' => '#eef6f7', 'surface' => '#fcfefe', 'text' => '#26363b', 'glow' => '53,106,131'],
            'royal' => ['accent' => '#97547b', 'accent2' => '#d29bb8', 'background' => '#faf0f5', 'surface' => '#fffbfd', 'text' => '#382a33', 'glow' => '151,84,123'],
            'forest' => ['accent' => '#4f745c', 'accent2' => '#98ae91', 'background' => '#f1f5ef', 'surface' => '#fcfefb', 'text' => '#2c382e', 'glow' => '79,116,92'],
            'gold' => ['accent' => '#966b43', 'accent2' => '#c99e6b', 'background' => '#f8f4ee', 'surface' => '#fffdf9', 'text' => '#332b25', 'glow' => '150,107,67'],
            'crimson' => ['accent' => '#9f5662', 'accent2' => '#d7989d', 'background' => '#fbf0ef', 'surface' => '#fffcfa', 'text' => '#392b2c', 'glow' => '159,86,98'],
        ];
        $palette = $palettes[$theme] ?? $palettes['neon'];
        $resolvedAccent = $accentColor ?: $palette['accent'];
        $resolvedAccent2 = $secondaryAccentColor ?: $palette['accent2'];
        $resolvedBackground = $backgroundColor ?: $palette['background'];
        $resolvedSurface = $surfaceColor ?: $palette['surface'];
        $resolvedText = $textColor ?: $palette['text'];
        $orderMap = [
            'services_experience_gallery' => ['services', 'experience', 'gallery'],
            'experience_services_gallery' => ['experience', 'services', 'gallery'],
            'gallery_services_experience' => ['gallery', 'services', 'experience'],
        ];
        $orderedSections = $orderMap[$sectionOrder] ?? $orderMap['services_experience_gallery'];
        $heroUrl = $heroImage ? tenant_asset($heroImage) : null;
    @endphp
    <style>
        :root{--accent:{{ $resolvedAccent }};--accent-2:{{ $resolvedAccent2 }};--glow:{{ $palette['glow'] }};--bg:{{ $resolvedBackground }};--surface:{{ $resolvedSurface }};--surface-2:color-mix(in srgb,var(--surface),var(--text) 3%);--line:color-mix(in srgb,var(--text),transparent 88%);--text:{{ $resolvedText }};--muted:color-mix(in srgb,var(--text),transparent 35%);--gold:#fbbf24;--shadow:0 22px 70px rgba(54,39,27,.12)}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;background:var(--bg);color:var(--text);font-family:"Alexandria",sans-serif;line-height:1.8;overflow-x:hidden}a{color:inherit;text-decoration:none}button,input{font:inherit}.container{width:min(1180px,calc(100% - 40px));margin:auto}.site-header{position:sticky;top:0;z-index:50;background:rgba(9,9,11,.84);backdrop-filter:blur(18px);border-bottom:1px solid var(--line)}.nav{height:76px;display:flex;align-items:center;justify-content:space-between;gap:28px}.identity{display:flex;align-items:center;gap:11px;min-width:0}.identity img{width:46px;height:46px;border-radius:12px;object-fit:contain;background:#fff}.identity-mark{display:grid;place-items:center;width:42px;height:42px;border-radius:12px;background:linear-gradient(135deg,var(--accent),var(--accent-2));font-size:1.2rem}.identity strong{font-size:1rem;max-width:230px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.nav-links{display:flex;align-items:center;gap:24px;font-size:.82rem;color:var(--muted)}.nav-links a:hover{color:#fff}.account{display:flex;align-items:center;gap:9px}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 19px;border:1px solid transparent;border-radius:11px;font-weight:700;font-size:.8rem;cursor:pointer;transition:.2s;background:none;color:var(--text)}.btn:hover{transform:translateY(-2px)}.btn-primary{background:linear-gradient(135deg,var(--accent),var(--accent-2));box-shadow:0 12px 28px rgba(var(--glow),.2)}.btn-outline{border-color:rgba(255,255,255,.2);background:rgba(255,255,255,.03)}
        .hero{position:relative;min-height:650px;display:flex;align-items:stretch;border-bottom:1px solid var(--line);overflow:hidden}.hero-inner{width:min(1360px,100%);margin:auto;display:grid;grid-template-columns:.8fr 1.2fr;min-height:650px}.hero-copy{display:flex;flex-direction:column;justify-content:center;padding:72px clamp(24px,5vw,78px);position:relative;z-index:2;background:linear-gradient(90deg,rgba(9,9,11,.98) 70%,rgba(9,9,11,.75))}.hero-copy h1{font-size:clamp(2.7rem,5.2vw,5.6rem);line-height:1.12;letter-spacing:-.06em;margin:0 0 22px}.hero-copy h1 span{display:block;color:var(--accent)}.hero-copy p{font-size:1.04rem;color:#d4d4d8;max-width:590px;margin:0 0 32px}.hero-actions{display:flex;gap:11px;flex-wrap:wrap}.hero-media{position:relative;min-height:560px;background:radial-gradient(circle at 65% 35%,rgba(var(--glow),.35),transparent 28%),linear-gradient(145deg,#1d1019,#09090b 68%)}.hero-media:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,#09090b 0,transparent 28%),repeating-linear-gradient(125deg,transparent 0 45px,rgba(255,255,255,.025) 46px 47px)}.hero-media:after{content:"";position:absolute;inset:0}.hero-media img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.hero-media.has-image:after{display:none}.hero-media.has-image:before{background:linear-gradient(90deg,#09090b 0,transparent 35%),linear-gradient(0deg,rgba(9,9,11,.25),transparent 45%)}.hero.immersive{display:block}.hero.immersive .hero-inner{grid-template-columns:1fr;min-height:720px;position:relative}.hero.immersive .hero-media{grid-row:1;grid-column:1}.hero.immersive .hero-copy{grid-row:1;grid-column:1;width:min(650px,90%);background:linear-gradient(90deg,rgba(9,9,11,.96),rgba(9,9,11,.72),transparent);align-self:stretch}
        .info-strip{border-bottom:1px solid var(--line);background:#0d0d10}.info-grid{min-height:96px;display:grid;grid-template-columns:repeat(3,1fr)}.info-item{padding:20px 26px;border-left:1px solid var(--line);display:flex;align-items:center;gap:13px}.info-item:last-child{border-left:0}.info-item i{font-style:normal;color:var(--accent);font-size:1.15rem}.info-item small{display:block;color:var(--muted);font-size:.69rem}.info-item strong{display:block;font-size:.82rem;margin-top:2px}
        .customer-panel{padding:26px 0;border-bottom:1px solid var(--line)}.customer-card{display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;padding:24px 28px;border:1px solid rgba(var(--glow),.3);border-radius:18px;background:linear-gradient(120deg,rgba(var(--glow),.12),transparent 55%),var(--surface)}.customer-welcome{display:flex;align-items:center;gap:14px}.avatar{width:46px;height:46px;border-radius:50%;display:grid;place-items:center;background:var(--accent);font-weight:800}.customer-welcome h2{font-size:1rem;margin:0}.customer-welcome p{color:var(--muted);font-size:.75rem;margin:2px 0 0}.wallet{display:flex;gap:10px}.wallet-item{min-width:145px;padding:12px 16px;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid var(--line)}.wallet-item small{display:block;color:var(--muted);font-size:.65rem}.wallet-item strong{font-size:1.05rem}.redeem{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:16px;padding-top:18px;border-top:1px solid var(--line)}.redeem p{margin:0;color:var(--muted);font-size:.75rem}.redeem form{display:flex;gap:8px}.redeem input{width:110px;background:#09090b;border:1px solid var(--line);border-radius:10px;padding:8px 10px;color:#fff;text-align:center}
        .section{padding:84px 0}.section-head{display:flex;justify-content:space-between;align-items:end;gap:30px;margin-bottom:34px}.section-head h2{font-size:clamp(1.8rem,3.5vw,3rem);line-height:1.3;margin:0;letter-spacing:-.04em}.section-head p{max-width:450px;margin:0;color:var(--muted);font-size:.86rem}.offers{background:radial-gradient(circle at 0 50%,rgba(var(--glow),.11),transparent 32%)}.offers-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.offer{position:relative;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:var(--surface);padding:26px}.offer:before{content:"";position:absolute;inset:0 0 auto;height:3px;background:linear-gradient(90deg,var(--accent),var(--accent-2))}.offer-type{font-size:.68rem;color:var(--accent);font-weight:700}.offer h3{font-size:1.15rem;margin:8px 0 16px}.offer-price{font-size:2.35rem;font-weight:800;line-height:1}.offer-price span{font-size:.72rem;color:var(--muted)}.offer-details{margin:22px 0;padding-top:16px;border-top:1px solid var(--line);display:grid;gap:8px;color:var(--muted);font-size:.74rem}.offer .btn{width:100%}.empty{grid-column:1/-1;padding:38px;border:1px dashed var(--line);border-radius:16px;text-align:center;color:var(--muted)}
        .experience{border-block:1px solid var(--line);background:var(--surface)}.experience-row{display:grid;grid-template-columns:repeat(4,1fr)}.experience-item{padding:30px;border-left:1px solid var(--line)}.experience-item:last-child{border-left:0}.experience-item b{display:block;font-size:.95rem;margin:14px 0 7px}.experience-item p{margin:0;color:var(--muted);font-size:.75rem}.experience-icon{font-size:1.55rem;color:var(--accent)}.gallery-grid{display:grid;grid-template-columns:repeat(4,1fr);grid-auto-rows:220px;gap:12px}.gallery-item{overflow:hidden;border-radius:15px;border:1px solid var(--line)}.gallery-item:first-child{grid-column:span 2;grid-row:span 2}.gallery-item img{width:100%;height:100%;object-fit:cover;transition:.35s}.gallery-item:hover img{transform:scale(1.035)}
        .about{padding:0 0 84px}.about-box{display:grid;grid-template-columns:1.15fr .85fr;border:1px solid var(--line);border-radius:20px;overflow:hidden;background:var(--surface)}.about-copy{padding:44px}.about-copy h2{font-size:2rem;margin:0 0 13px}.about-copy p{color:var(--muted);margin:0}.contact-list{padding:34px;background:linear-gradient(145deg,rgba(var(--glow),.14),transparent),#151519;display:grid;align-content:center;gap:15px}.contact-row{display:flex;gap:12px;align-items:flex-start}.contact-row i{font-style:normal;color:var(--accent)}.contact-row small{display:block;color:var(--muted);font-size:.65rem}.contact-row strong{font-size:.78rem;word-break:break-word}.social{display:flex;gap:9px;margin-top:5px}.social a{display:grid;place-items:center;width:38px;height:38px;border-radius:10px;border:1px solid var(--line)}footer{padding:34px 0;border-top:1px solid var(--line);color:var(--muted);font-size:.75rem}.footer-inner{display:flex;justify-content:space-between;align-items:center;gap:20px}
        @media(max-width:920px){.nav-links{display:none}.hero-inner{grid-template-columns:1fr}.hero-copy{order:2;text-align:center;background:#09090b;padding:46px 24px}.hero-copy p{margin-inline:auto}.hero-actions{justify-content:center}.hero-media{min-height:430px;order:1}.hero-media:before{background:linear-gradient(0deg,#09090b,transparent 40%)}.hero.immersive .hero-copy{width:100%;align-self:end;min-height:410px}.info-grid{grid-template-columns:1fr}.info-item{border-left:0;border-bottom:1px solid var(--line)}.customer-card,.about-box{grid-template-columns:1fr}.offers-grid{grid-template-columns:1fr 1fr}.experience-row{grid-template-columns:1fr 1fr}.experience-item:nth-child(2){border-left:0}.gallery-grid{grid-template-columns:repeat(2,1fr)}}
        @media(max-width:620px){.container{width:min(100% - 24px,1180px)}.nav{height:66px}.identity strong{max-width:125px}.account .btn-outline{display:none}.hero,.hero-inner{min-height:auto}.hero-media{min-height:330px}.hero-copy h1{font-size:2.5rem}.section{padding:62px 0}.section-head{display:block;text-align:center}.section-head p{margin:10px auto 0}.offers-grid,.experience-row{grid-template-columns:1fr}.experience-item{border-left:0;border-bottom:1px solid var(--line)}.gallery-grid{grid-auto-rows:160px}.gallery-item:first-child{grid-row:span 1}.customer-card{padding:20px}.customer-welcome,.wallet,.redeem,.redeem form{flex-wrap:wrap}.wallet-item{flex:1}.footer-inner{flex-direction:column;text-align:center}}
        body{background:radial-gradient(circle at 85% -8%,rgba(var(--glow),.11),transparent 32%),var(--bg)}
        .btn-primary{background:var(--accent);box-shadow:0 12px 30px rgba(var(--glow),.22)}.btn-primary:hover{background:color-mix(in srgb,var(--accent),var(--accent-2) 24%)}
        .services{border-block:1px solid var(--line);background:linear-gradient(180deg,transparent,color-mix(in srgb,var(--surface),transparent 45%),transparent)}
        .services-showcase-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .service-showcase{display:grid;grid-template-columns:minmax(175px,.38fr) minmax(0,.62fr);min-height:270px;overflow:hidden;border:1px solid var(--line);border-radius:20px;background:linear-gradient(145deg,var(--surface),color-mix(in srgb,var(--surface),var(--bg) 22%));box-shadow:0 18px 50px rgba(0,0,0,.18);transition:border-color .25s,transform .25s}
        .service-showcase:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--accent),transparent 35%)}
        .service-showcase:last-child:nth-child(odd){grid-column:1/-1;grid-template-columns:minmax(240px,.3fr) minmax(0,.7fr)}
        .service-copy{padding:28px 24px;display:flex;flex-direction:column;align-items:flex-start;justify-content:center;position:relative}
        .service-number{color:var(--accent);font-size:.78rem;letter-spacing:.08em;margin-bottom:14px}.service-copy h3{font-size:1.35rem;line-height:1.35;margin:0 0 8px}.service-copy p{margin:0;color:var(--muted);font-size:.76rem}.service-photo-count{display:inline-flex;align-items:center;gap:7px;margin-top:22px;padding-top:14px;border-top:1px solid var(--line);color:var(--muted);font-size:.69rem}
        .service-mosaic{direction:ltr;display:grid;grid-template-columns:1.65fr .9fr;grid-template-rows:1fr 1fr;gap:8px;padding:8px;min-height:270px;background:color-mix(in srgb,var(--bg),transparent 22%)}
        .service-photo{position:relative;overflow:hidden;margin:0;border-radius:12px;background:var(--surface-2)}.service-photo:first-child{grid-row:1/-1}.service-photo img{width:100%;height:100%;object-fit:cover;transition:transform .45s}.service-showcase:hover .service-photo img{transform:scale(1.025)}
        .service-mosaic.images-1{grid-template-columns:1fr}.service-mosaic.images-2{grid-template-columns:1fr 1fr;grid-template-rows:1fr}.service-mosaic.images-2 .service-photo:first-child{grid-row:auto}.service-more{position:absolute;inset:0;display:grid;place-items:center;background:rgba(5,8,15,.62);color:#fff;font-weight:800;font-size:1rem;backdrop-filter:blur(2px)}
        .site-header{background:color-mix(in srgb,var(--bg),transparent 12%)}.hero-copy{background:linear-gradient(90deg,color-mix(in srgb,var(--bg),transparent 1%) 70%,color-mix(in srgb,var(--bg),transparent 22%))}.hero-copy p{color:var(--muted)}.hero-media{background:radial-gradient(circle at 65% 35%,rgba(var(--glow),.24),transparent 30%),linear-gradient(145deg,var(--surface),var(--bg) 68%)}.hero-media:before{background:linear-gradient(90deg,var(--bg) 0,transparent 28%),repeating-linear-gradient(125deg,transparent 0 45px,color-mix(in srgb,var(--text),transparent 97%) 46px 47px)}.hero-media:after{content:"";display:none}.hero-media.has-image:before{background:linear-gradient(90deg,var(--bg) 0,transparent 35%),linear-gradient(0deg,color-mix(in srgb,var(--bg),transparent 70%),transparent 45%)}.hero.immersive .hero-copy{background:linear-gradient(90deg,color-mix(in srgb,var(--bg),transparent 3%),color-mix(in srgb,var(--bg),transparent 28%),transparent)}.info-strip{background:var(--surface)}.redeem input{background:var(--bg);color:var(--text)}.contact-list{background:linear-gradient(145deg,rgba(var(--glow),.1),transparent),var(--surface-2)}.salon-hero-art{position:absolute;inset:8% 10%;display:grid;place-items:center;overflow:hidden;border:1px solid color-mix(in srgb,var(--accent),transparent 80%);border-radius:46% 46% 24px 24px;background:radial-gradient(circle at 50% 40%,color-mix(in srgb,var(--accent-2),white 72%),transparent 45%),linear-gradient(145deg,color-mix(in srgb,var(--accent),white 86%),var(--surface));box-shadow:0 28px 70px rgba(54,39,27,.12)}.salon-hero-art:before,.salon-hero-art:after{content:"";position:absolute;border:1px solid color-mix(in srgb,var(--accent),transparent 78%);border-radius:50%}.salon-hero-art:before{width:65%;aspect-ratio:1}.salon-hero-art:after{width:48%;aspect-ratio:1}.salon-vase{position:absolute;bottom:10%;left:17%;width:18%;height:35%;display:grid;place-items:center;border-radius:42% 42% 14px 14px;background:linear-gradient(90deg,#e7d7c5,#fffaf1 48%,#d4bba1);color:var(--accent);font-size:clamp(1.3rem,3vw,2.7rem);box-shadow:0 22px 32px rgba(54,39,27,.14)}.salon-product{position:absolute;bottom:13%;right:19%;width:14%;height:42%;border:1px solid rgba(90,65,44,.12);border-radius:10px 10px 16px 16px;background:linear-gradient(90deg,#d6a678,#f2d4b1 50%,#c48e5e);box-shadow:0 18px 25px rgba(54,39,27,.12)}.salon-product:before{content:"";position:absolute;top:-14%;left:34%;width:32%;height:16%;border-radius:4px 4px 0 0;background:#9b7554}.salon-product--small{right:36%;height:29%;width:11%;background:linear-gradient(90deg,#c38e92,#f0d3d4 50%,#ba7f84)}.salon-art-label{position:absolute;top:18%;right:13%;padding:9px 13px;border:1px solid color-mix(in srgb,var(--accent),transparent 80%);border-radius:12px;background:color-mix(in srgb,var(--surface),transparent 8%);color:var(--accent);font-size:.66rem;font-weight:700;box-shadow:0 10px 25px rgba(54,39,27,.08)}.salon-art-sparkle{position:absolute;top:24%;left:27%;color:var(--accent-2);font-size:1.6rem}
        .location{padding:0 0 84px}.location-grid{display:grid;grid-template-columns:.75fr 1.25fr;min-height:390px;border:1px solid var(--line);border-radius:20px;overflow:hidden;background:var(--surface)}.location-copy{padding:42px;display:flex;flex-direction:column;justify-content:center}.location-copy h2{font-size:2rem;line-height:1.35;margin:0 0 12px}.location-copy p{color:var(--muted);margin:0 0 24px}.location-copy .btn{align-self:flex-start}.map-frame{min-height:390px;background:var(--surface-2)}.map-frame iframe{display:block;width:100%;height:100%;min-height:390px;border:0}
        @media(max-width:1050px){.services-showcase-grid{grid-template-columns:1fr}.service-showcase:last-child:nth-child(odd){grid-column:auto;grid-template-columns:minmax(175px,.38fr) minmax(0,.62fr)}}
        @media(max-width:920px){.location-grid{grid-template-columns:1fr}.map-frame{order:-1}.location-copy{text-align:center}.location-copy .btn{align-self:center}}
        @media(max-width:620px){.service-showcase,.service-showcase:last-child:nth-child(odd){grid-template-columns:1fr}.service-copy{order:2;padding:24px}.service-mosaic{order:1;min-height:250px}.location{padding-bottom:62px}.location-copy{padding:30px 22px}.map-frame,.map-frame iframe{min-height:300px}}
        .skip-link{position:absolute;top:-60px;right:16px;z-index:100;background:var(--text);color:var(--bg);padding:9px 14px;border-radius:8px}.skip-link:focus{top:12px}
        a:focus-visible,button:focus-visible,select:focus-visible,input:focus-visible{outline:3px solid var(--accent);outline-offset:3px}
        .btn-primary{color:#fffaf3}.btn-outline{border-color:color-mix(in srgb,var(--text),transparent 78%);background:color-mix(in srgb,var(--surface),transparent 20%)}.nav-links a:hover{color:var(--accent)}
        .salon-eyebrow{display:inline-flex;align-self:flex-start;align-items:center;gap:8px;margin-bottom:15px;color:var(--accent);font-size:.75rem;font-weight:700}.salon-eyebrow:before{content:"";width:7px;height:7px;border-radius:50%;background:var(--accent-2)}
        .branch-picker{display:flex;align-items:center;gap:10px;margin:-10px 0 22px;color:var(--muted);font-size:.77rem}.branch-picker select{min-height:42px;min-width:180px;padding:0 12px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text)}
        .salon-service-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.salon-service-card{position:relative;padding:22px;border:1px solid var(--line);border-radius:17px;background:var(--surface);box-shadow:0 14px 34px rgba(54,39,27,.06)}.service-card-mark{display:grid;place-items:center;width:37px;height:37px;margin-bottom:14px;border-radius:12px;background:color-mix(in srgb,var(--accent),transparent 90%);color:var(--accent)}.service-category{color:var(--accent);font-size:.66rem;font-weight:700}.salon-service-card h3{margin:7px 0;font-size:1.03rem}.salon-service-card p{min-height:42px;margin:0;color:var(--muted);font-size:.73rem}.service-meta{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:18px 0 14px;padding-top:13px;border-top:1px solid var(--line);font-size:.72rem}.service-meta span{color:var(--muted)}.service-meta strong{color:var(--accent);font-size:.91rem}.service-booking{width:100%}.service-empty{padding:18px;border:1px dashed var(--line);border-radius:14px;color:var(--muted);font-size:.8rem}.showcase-title{margin:38px 0 16px;font-size:1.12rem}
        .experience-item,.offer,.service-showcase,.customer-card,.location-grid,.about-box,.info-item,.wallet-item,.contact-list{box-shadow:0 14px 40px rgba(54,39,27,.04)}
        .site-header{border-color:var(--line)}.hero{background:radial-gradient(circle at 84% 14%,rgba(var(--glow),.08),transparent 32%),var(--bg)}.hero-copy{background:linear-gradient(90deg,color-mix(in srgb,var(--bg),transparent 1%),color-mix(in srgb,var(--bg),transparent 12%))}.hero-copy h1{color:var(--text)}.info-strip{border-color:var(--line)}.section-head p,.experience-item p,.about-copy p,.contact-row small{color:var(--muted)}.footer-inner{border-color:var(--line)}
        @media(max-width:920px){.nav-links{display:flex;order:3;width:100%;justify-content:center;flex-wrap:wrap;gap:12px 20px;padding:8px 0}.nav{height:auto;min-height:76px;flex-wrap:wrap;padding-top:8px;padding-bottom:8px}.salon-service-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:620px){.nav-links{justify-content:flex-start;flex-wrap:nowrap;overflow-x:auto;white-space:nowrap}.account .btn{padding:0 11px}.salon-service-grid{grid-template-columns:1fr}.salon-service-card p{min-height:0}.branch-picker{align-items:stretch;flex-wrap:wrap}.branch-picker label{width:100%}.branch-picker select{flex:1;min-width:0}.hero-media{min-height:340px}.hero-copy{padding:50px 24px}.hero-copy h1{font-size:clamp(2.2rem,10vw,3.5rem)}}
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{transition-duration:.01ms!important;animation-duration:.01ms!important;animation-iteration-count:1!important}}
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">تجاوز إلى المحتوى</a>
    <header class="site-header"><div class="container nav"><a href="#top" class="identity">@if($logo)<img src="{{ tenant_asset($logo) }}" alt="شعار {{ $shopName }}">@else<span class="identity-mark" aria-hidden="true">✂</span>@endif<strong>{{ $shopName }}</strong></a><nav class="nav-links" aria-label="التنقل الرئيسي">@if($visibility['services'] && ($salonServices->isNotEmpty() || count($services)))<a href="#services">الخدمات</a>@endif<a href="{{ route('tenant.menu') }}">المنتجات</a>@if($visibility['gallery'] && count($gallery))<a href="#gallery">الصور</a>@endif @if($locationUrl)<a href="#location">الموقع</a>@endif @if($visibility['contact'])<a href="#contact">تواصل معنا</a>@endif</nav><div class="account">@if(Auth::guard('customer')->check())<a class="btn btn-outline" href="/customer">حسابي</a><form action="/customer/logout" method="POST">@csrf<button class="btn btn-primary" type="submit">خروج</button></form>@else<a class="btn btn-outline" href="/customer/login">دخول</a><a class="btn btn-primary" href="/customer/register">حساب جديد</a>@endif</div></div></header>

    <main id="main-content">
        <section class="hero {{ $heroLayout }}"><div class="hero-inner"><div class="hero-copy"><span class="salon-eyebrow">عناية وجمال بتجربة تستحقها</span><h1>{{ $title }}</h1><p>{{ $description }}</p><div class="hero-actions">@if($primaryCtaUrl)<a class="btn btn-primary" href="{{ $primaryCtaUrl }}">{{ $primaryCta }}</a>@endif<a class="btn btn-outline" href="{{ route('tenant.menu') }}">{{ $secondaryCta }}</a></div></div><div class="hero-media {{ $heroUrl ? 'has-image' : '' }}">@if($heroUrl)<img src="{{ $heroUrl }}" alt="أجواء {{ $shopName }}">@else<div class="salon-hero-art" aria-hidden="true"><span class="salon-art-label">عناية مختارة لك</span><span class="salon-art-sparkle">✦</span><span class="salon-vase">✂</span><span class="salon-product salon-product--small"></span><span class="salon-product"></span></div>@endif</div></div></section>

        @if($visibility['contact'] && ($phone || $address || $openingHours))
            <section class="info-strip"><div class="container info-grid">@if($phone)<a class="info-item" href="tel:{{ $phone }}"><i>☎</i><span><small>اتصل بنا</small><strong>{{ $phone }}</strong></span></a>@endif @if($openingHours)<div class="info-item"><i>◷</i><span><small>مواعيد العمل</small><strong>{{ $openingHours }}</strong></span></div>@endif @if($address)<div class="info-item"><i>⌖</i><span><small>عنوان الصالون</small><strong>{{ $address }}</strong></span></div>@endif</div></section>
        @endif

        @if(Auth::guard('customer')->check() && $visibility['loyalty'])
            @php $loggedCustomer = Auth::guard('customer')->user(); @endphp
            <section class="customer-panel" id="dashboard"><div class="container customer-card"><div class="customer-welcome"><span class="avatar">{{ mb_substr($loggedCustomer->name, 0, 1) }}</span><div><h2>أهلًا {{ $loggedCustomer->name }}</h2><p>رصيدك ونقاطك جاهزين للاستخدام في زيارتك القادمة.</p></div></div><div class="wallet"><div class="wallet-item"><small>رصيد المحفظة</small><strong>{{ number_format($loggedCustomer->balance, 2) }} ج.م</strong></div><div class="wallet-item"><small>نقاط الولاء</small><strong>{{ number_format($loggedCustomer->points, 0) }} نقطة</strong></div></div><div class="redeem"><p>كل {{ $pointsRate }} نقاط تساوي جنيهًا واحدًا في محفظتك.</p>@if($loggedCustomer->points > 0)<form method="POST" action="{{ route('tenant.customer.convert-points') }}">@csrf<input type="number" name="points" min="1" max="{{ $loggedCustomer->points }}" value="{{ $loggedCustomer->points }}" aria-label="النقاط المراد استبدالها"><button class="btn btn-primary" type="submit">حوّل إلى رصيد</button></form>@endif</div></div></section>
        @endif

        @foreach($orderedSections as $sectionName)
            @if($sectionName === 'services' && $visibility['services'] && ($salonServices->isNotEmpty() || count($services)))
                <section class="section services" id="services">
                    <div class="container">
                        <div class="section-head">
                            <h2>خدماتنا وأسعارها</h2>
                            <p>تعرّف على الخدمات المتاحة في الفرع واختر ما يناسبك. للاستفسار عن موعد، تواصل مع الصالون مباشرة.</p>
                        </div>

                        @if($branches->count() > 1)
                            <form class="branch-picker" action="{{ route('tenant.landing') }}" method="GET">
                                <label for="service-branch">اعرض خدمات فرع</label>
                                <select id="service-branch" name="branch_id">
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((int) $branch->id === $selectedBranchId)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-outline" type="submit">عرض الخدمات</button>
                            </form>
                        @endif

                        @if($salonServices->isNotEmpty())
                            <div class="salon-service-grid">
                                @foreach($salonServices as $salonService)
                                    <article class="salon-service-card">
                                        <span class="service-card-mark" aria-hidden="true">✦</span>
                                        @if($salonService->category)<span class="service-category">{{ $salonService->category }}</span>@endif
                                        <h3>{{ $salonService->name }}</h3>
                                        @if($salonService->description)<p>{{ $salonService->description }}</p>@endif
                                        <div class="service-meta"><span>◷ {{ $salonService->duration_minutes }} دقيقة</span><strong>{{ number_format((float) $salonService->price, 0) }} ج.م</strong></div>
                                        <a class="btn btn-primary service-booking" href="{{ route('tenant.bookings.create', ['branch_id' => $selectedBranchId, 'service_id' => $salonService->id]) }}">احجز هذه الخدمة</a>
                                        @if($whatsapp)
                                            <a class="btn btn-primary service-booking" target="_blank" rel="noopener" href="https://wa.me/{{ $whatsapp }}?text={{ urlencode('مرحبًا، أود الاستفسار عن خدمة '.$salonService->name) }}">استفسر عبر واتساب</a>
                                        @elseif($phone)
                                            <a class="btn btn-primary service-booking" href="tel:{{ $phone }}">اتصل للاستفسار</a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @elseif($branches->isNotEmpty() && ! count($services))
                            <p class="service-empty">لا توجد خدمات مضافة لهذا الفرع حتى الآن. تواصل معنا لمعرفة الخيارات المتاحة.</p>
                        @endif

                        @if(count($services))
                            <h3 class="showcase-title">اكتشف أجواء وخدمات {{ $shopName }}</h3>
                        <div class="services-showcase-grid">
                            @foreach($services as $index => $service)
                                @php
                                    $serviceImages = $service['images'];
                                    $serviceImageCount = count($serviceImages);
                                @endphp

                                <article class="service-showcase">
                                    <div class="service-copy">
                                        <span class="service-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                        <h3>{{ $service['title'] }}</h3>
                                        @if($service['description'])
                                            <p>{{ $service['description'] }}</p>
                                        @endif
                                        <span class="service-photo-count" aria-label="عدد الصور">
                                            <span aria-hidden="true">▦</span>
                                            {{ $serviceImageCount === 1 ? 'صورة واحدة' : $serviceImageCount.' صور' }}
                                        </span>
                                    </div>

                                    <div class="service-mosaic images-{{ min($serviceImageCount, 3) }}">
                                        @foreach(array_slice($serviceImages, 0, 3) as $imageIndex => $serviceImage)
                                            <figure class="service-photo">
                                                <img src="{{ tenant_asset($serviceImage) }}" alt="{{ $service['title'] }} في {{ $shopName }}{{ $imageIndex ? ' - صورة '.($imageIndex + 1) : '' }}" loading="lazy">
                                                @if($imageIndex === 2 && $serviceImageCount > 3)
                                                    <span class="service-more" aria-label="{{ $serviceImageCount - 3 }} صور إضافية">+{{ $serviceImageCount - 3 }}</span>
                                                @endif
                                            </figure>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </section>
            @elseif($sectionName === 'experience' && $visibility['experience'])
                <section class="section experience" id="experience"><div class="container"><div class="section-head"><h2>عناية متكاملة تناسبك</h2><p>استمتع بخدمات صالون احترافية، وفريق يهتم بتفاصيل تجربتك من البداية للنهاية.</p></div><div class="experience-row"><article class="experience-item"><span class="experience-icon">✂️</span><b>خدمات متنوعة</b><p>خدمات تصفيف وعناية تناسب احتياجاتك ومناسباتك.</p></article><article class="experience-item"><span class="experience-icon">✦</span><b>فريق متخصص</b><p>خبرة واهتمام بالتفاصيل لضمان نتيجة ترضيك.</p></article><article class="experience-item"><span class="experience-icon">♡</span><b>منتجات مختارة</b><p>منتجات عناية وتجميل متاحة لتكمل روتينك.</p></article><article class="experience-item"><span class="experience-icon">★</span><b>عناية واهتمام</b><p>تجربة مريحة وخدمة شخصية في كل زيارة.</p></article></div></div></section>
            @elseif($sectionName === 'gallery' && $visibility['gallery'] && count($gallery))
                <section class="section" id="gallery"><div class="container"><div class="section-head"><h2>شوف أجواء الصالون</h2></div><div class="gallery-grid">@foreach($gallery as $image)<figure class="gallery-item"><img src="{{ tenant_asset($image) }}" alt="صورة من {{ $shopName }}" loading="lazy"></figure>@endforeach</div></div></section>
            @endif
        @endforeach

        @if($locationUrl)
            <section class="location" id="location"><div class="container location-grid"><div class="location-copy"><h2>تعالى لنا بسهولة</h2><p>{{ $address ?: 'افتح الموقع على الخريطة واحصل على الاتجاهات مباشرة.' }}</p><a class="btn btn-primary" href="{{ $locationUrl }}" target="_blank" rel="noopener">افتح الاتجاهات</a></div><div class="map-frame"><iframe src="{{ $mapEmbedUrl }}" title="موقع {{ $shopName }} على الخريطة" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div></div></section>
        @endif

        @if($visibility['about'] || $visibility['contact'])
            <section class="about" id="contact"><div class="container about-box">@if($visibility['about'])<div class="about-copy"><h2>عن {{ $shopName }}</h2><p>{{ $about }}</p></div>@endif @if($visibility['contact'])<div class="contact-list">@if($address)<div class="contact-row"><i>⌖</i><span><small>العنوان</small><strong>{{ $address }}</strong></span></div>@endif @if($phone)<div class="contact-row"><i>☎</i><span><small>الهاتف</small><strong><a href="tel:{{ $phone }}">{{ $phone }}</a></strong></span></div>@endif @if($email)<div class="contact-row"><i>✉</i><span><small>البريد الإلكتروني</small><strong><a href="mailto:{{ $email }}">{{ $email }}</a></strong></span></div>@endif @if($whatsapp)<div class="contact-row"><i>◉</i><span><small>واتساب</small><strong><a href="https://wa.me/{{ $whatsapp }}">{{ $whatsapp }}</a></strong></span></div>@endif @if($facebook || $instagram)<div class="social">@if($facebook)<a href="{{ $facebook }}" target="_blank" rel="noopener" aria-label="Facebook">f</a>@endif @if($instagram)<a href="{{ $instagram }}" target="_blank" rel="noopener" aria-label="Instagram">◎</a>@endif</div>@endif</div>@endif</div></section>
        @endif
    </main>
    <footer><div class="container footer-inner"><span>{{ $shopName }}</span><span>© {{ date('Y') }} جميع الحقوق محفوظة</span><a href="{{ route('tenant.menu') }}">المنتجات</a></div></footer>
</body>
</html>
