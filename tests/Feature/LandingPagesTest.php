<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Tenant\LandingPageController;
use App\Models\Tenant\Offer;
use App\Models\Tenant\SystemConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPagesTest extends TestCase
{
    public function test_central_landing_uses_real_product_screenshots_in_rtl(): void
    {
        $response = $this->view('landing');

        $response->assertSee('dir="rtl"', false)
            ->assertSee('شغّل صالتك بذكاء')
            ->assertSee('كل عمليات صالتك في رحلة واحدة مترابطة')
            ->assertSee('images/ps-web-device-control.webp', false)
            ->assertSee('images/ps-web-direct-sales.webp', false)
            ->assertSee('images/ps-web-shift-close.webp', false)
            ->assertSee('PS Web — التحكم في الأجهزة')
            ->assertSee('object-fit:contain', false)
            ->assertDontSee('ps-web-operations.webp', false)
            ->assertDontSee('unsplash.com', false);
    }

    public function test_tenant_landing_renders_owner_customization_and_real_offers(): void
    {
        $offer = new Offer([
            'name' => 'عرض الأبطال',
            'play_type' => 'زوجي',
            'period' => 120,
            'price' => 150,
            'devices' => 'PS5',
            'is_active' => true,
        ]);

        $view = $this->view('tenant.landing', $this->tenantViewData([
            'offers' => collect([$offer]),
            'shopName' => 'ليفل أب',
            'title' => 'أهلاً بكم في ليفل أب',
            'accentColor' => '#123456',
            'heroLayout' => 'immersive',
            'services' => [[
                'title' => 'ركن البلايستيشن',
                'description' => 'أحدث الأجهزة والألعاب.',
                'images' => [
                    'landing-services/playstation.jpg',
                    'landing-services/playstation-side.jpg',
                    'landing-services/playstation-room.jpg',
                    'landing-services/playstation-extra.jpg',
                ],
            ]],
        ]));

        $view->assertSee('dir="rtl"', false)
            ->assertSee('--accent:#123456', false)
            ->assertSee('hero immersive', false)
            ->assertSee('عرض الأبطال')
            ->assertSee('ركن البلايستيشن')
            ->assertSee('landing-services/playstation.jpg', false)
            ->assertSee('landing-services/playstation-side.jpg', false)
            ->assertSee('landing-services/playstation-room.jpg', false)
            ->assertDontSee('landing-services/playstation-extra.jpg', false)
            ->assertSee('+1')
            ->assertSee('services-showcase-grid', false)
            ->assertSee('عن ليفل أب')
            ->assertDontSee('عن أهلاً بكم في ليفل أب')
            ->assertSee('id="location"', false)
            ->assertSee('افتح الاتجاهات')
            ->assertSee('150')
            ->assertSee('افتح المنيو')
            ->assertDontSee('gaming-lounge-fallback', false)
            ->assertDontSee('unsplash.com', false);
    }

    public function test_tenant_landing_controller_sanitizes_owner_settings_and_honors_visibility(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('landing-services/cafe.jpg', 'image-content');
        Storage::disk('public')->put('landing-services/cafe-seating.jpg', 'image-content');
        Storage::disk('public')->put('landing-services/legacy.jpg', 'image-content');

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->nullable();
            $table->timestamps();
        });

        SystemConfig::set('landing_title', '<script>alert(1)</script> صالة آمنة', 'landing');
        SystemConfig::set('landing_theme', 'unsupported-theme', 'landing');
        SystemConfig::set('landing_accent_color', 'red;background:url(javascript:alert(1))', 'landing');
        SystemConfig::set('landing_facebook', 'javascript:alert(1)', 'landing');
        SystemConfig::set('landing_location_url', 'javascript:alert(1)', 'landing');
        SystemConfig::set('landing_show_offers', '0', 'landing');
        SystemConfig::set('landing_show_gallery', '0', 'landing');
        SystemConfig::set('landing_show_services', '1', 'landing');
        SystemConfig::set('landing_services', json_encode([
            [
                'title' => '<script>مقهى</script>',
                'description' => '<b>مشروبات وسناكس</b>',
                'image' => [
                    'landing-services/cafe.jpg',
                    'landing-services/cafe-seating.jpg',
                    '../outside.jpg',
                ],
            ],
            [
                'title' => 'صورة غير آمنة',
                'description' => 'يجب ألا تظهر',
                'image' => '../outside.jpg',
            ],
            [
                'title' => 'Legacy service',
                'description' => 'Legacy single image remains supported',
                'image' => 'landing-services/legacy.jpg',
            ],
        ], JSON_UNESCAPED_UNICODE), 'landing');

        $html = app(LandingPageController::class)->index()->render();

        $this->assertStringContainsString('alert(1) صالة آمنة', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('--accent:#7c5cff', $html);
        $this->assertStringNotContainsString('javascript:alert(1)', $html);
        $this->assertStringNotContainsString('id="offers"', $html);
        $this->assertStringNotContainsString('id="gallery"', $html);
        $this->assertStringNotContainsString('id="location"', $html);
        $this->assertStringContainsString('id="services"', $html);
        $this->assertStringContainsString('مقهى', $html);
        $this->assertStringContainsString('مشروبات وسناكس', $html);
        $this->assertStringContainsString('landing-services/cafe.jpg', $html);
        $this->assertStringContainsString('landing-services/cafe-seating.jpg', $html);
        $this->assertStringContainsString('landing-services/legacy.jpg', $html);
        $this->assertStringContainsString('2 صور', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('outside.jpg', $html);

        SystemConfig::set('landing_location_url', 'https://maps.app.goo.gl/example', 'landing');
        $mapHtml = app(LandingPageController::class)->index()->render();

        $this->assertStringContainsString('id="location"', $mapHtml);
        $this->assertStringContainsString('https://maps.app.goo.gl/example', $mapHtml);

        Schema::dropIfExists('system_configs');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function tenantViewData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'صالة الاختبار',
            'shopName' => 'صالة الاختبار',
            'description' => 'جلسات لعب ممتعة وعروض مناسبة.',
            'about' => 'صالة مجهزة للعب واللمة.',
            'phone' => '',
            'whatsapp' => '',
            'email' => '',
            'address' => '',
            'openingHours' => '',
            'locationUrl' => 'https://maps.google.com/?q=Cairo',
            'mapEmbedUrl' => 'https://www.google.com/maps?q=Cairo&output=embed',
            'facebook' => '',
            'instagram' => '',
            'gallery' => [],
            'services' => [],
            'offers' => new Collection,
            'pointsRate' => 10,
            'primaryCta' => 'شوف العروض',
            'secondaryCta' => 'افتح المنيو',
            'theme' => 'neon',
            'accentColor' => '',
            'secondaryAccentColor' => '',
            'backgroundColor' => '',
            'surfaceColor' => '',
            'textColor' => '',
            'heroLayout' => 'split',
            'sectionOrder' => 'offers_experience_gallery',
            'visibility' => [
                'offers' => true,
                'services' => true,
                'experience' => true,
                'gallery' => true,
                'about' => true,
                'contact' => true,
                'loyalty' => true,
            ],
            'logo' => null,
            'heroImage' => null,
        ], $overrides);
    }
}
