<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Tenant\LandingPageController;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\SystemConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LandingPagesTest extends TestCase
{
    public function test_central_landing_promotes_salon_management_with_illustrative_workflow_and_gallery_screens(): void
    {
        $response = $this->view('landing');
        $renderedView = (string) $response;

        foreach ([
            'esalone-appointments.svg',
            'esalone-services.svg',
            'esalone-checkout.svg',
            'esalone-dashboard.svg',
        ] as $image) {
            $this->assertFileExists(public_path('images/'.$image));
            $this->assertSame(2, substr_count($renderedView, $image));
        }

        $this->assertSame(8, substr_count($renderedView, 'class="image-zoom-trigger"'));

        $response->assertSee('dir="rtl"', false)
            ->assertSee('Esalone | إدارة متكاملة للصالونات ومراكز التجميل')
            ->assertSee('كل تفاصيل صالونك')
            ->assertSee('تصوّر توضيحي لجدول مواعيد الصالون')
            ->assertSee('href="https://etech-valley.com/customer"', false)
            ->assertSee('طلب الاشتراك')
            ->assertSee('رحلة العميل من الحجز إلى التحصيل')
            ->assertSee('مواعيد بلا تعارض')
            ->assertSee('الخدمات وفريق العمل')
            ->assertSee('مخزون المنتجات')
            ->assertSee('class="workflow-image"', false)
            ->assertSee('object-fit:contain', false)
            ->assertSee('id="image-lightbox"', false)
            ->assertSee('aria-label="إغلاق الصورة"', false)
            ->assertSee('معرض الواجهات')
            ->assertSee('واجهة جدول مواعيد Esalone، تصميم توضيحي')
            ->assertSee('واجهة نقطة البيع وتحصيل الخدمة في Esalone، تصميم توضيحي')
            ->assertSee('id="workflow"', false)
            ->assertSee('id="gallery"', false)
            ->assertSee('class="skip-link"', false)
            ->assertSee('prefers-reduced-motion', false)
            ->assertDontSee('دخول الموظفين')
            ->assertDontSee('/admin/login')
            ->assertDontSee('بلايستيشن')
            ->assertDontSee('الكافيه')
            ->assertDontSee('PS Web')
            ->assertDontSee('images/ps-web-', false);
    }

    public function test_tenant_landing_renders_service_catalog_and_owner_customization(): void
    {
        $salonService = new SalonService([
            'name' => 'قص وتصفيف',
            'category' => 'الشعر',
            'description' => 'قص وتصفيف يناسبك.',
            'duration_minutes' => 45,
            'price' => 250,
            'is_active' => true,
        ]);
        $branch = new Branch(['name' => 'الفرع الرئيسي', 'is_active' => true]);
        $branch->setAttribute('id', 1);

        $view = $this->view('tenant.landing', $this->tenantViewData([
            'shopName' => 'ليفل أب',
            'title' => 'أهلاً بكم في ليفل أب',
            'accentColor' => '#123456',
            'heroLayout' => 'immersive',
            'salonServices' => collect([$salonService]),
            'branches' => collect([$branch]),
            'selectedBranchId' => 1,
            'services' => [[
                'title' => 'ركن العناية',
                'description' => 'خدمات عناية واسترخاء.',
                'images' => [
                    'landing-services/salon.jpg',
                    'landing-services/salon-side.jpg',
                    'landing-services/salon-room.jpg',
                    'landing-services/salon-extra.jpg',
                ],
            ]],
        ]));

        $view->assertSee('dir="rtl"', false)
            ->assertSee('--accent:#123456', false)
            ->assertSee('hero immersive', false)
            ->assertSee('href="https://wa.me/201234567890?text=', false)
            ->assertSee('قص وتصفيف')
            ->assertSee('45 دقيقة')
            ->assertSee('250 ج.م')
            ->assertSee('ركن العناية')
            ->assertSee('landing-services/salon.jpg', false)
            ->assertSee('landing-services/salon-side.jpg', false)
            ->assertSee('landing-services/salon-room.jpg', false)
            ->assertDontSee('landing-services/salon-extra.jpg', false)
            ->assertSee('+1')
            ->assertSee('services-showcase-grid', false)
            ->assertSee('عن ليفل أب')
            ->assertDontSee('عن أهلاً بكم في ليفل أب')
            ->assertSee('id="location"', false)
            ->assertSee('افتح الاتجاهات')
            ->assertSee('افتح المنيو')
            ->assertSee('class="salon-hero-art"', false)
            ->assertDontSee('GAME ON')
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
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('salon_services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        DB::table('branches')->insert([
            ['id' => 1, 'name' => 'الفرع الأول', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'الفرع الثاني', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('salon_services')->insert([
            ['branch_id' => 1, 'name' => 'قص شعر', 'category' => 'الشعر', 'description' => 'قص احترافي', 'duration_minutes' => 30, 'price' => 150, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['branch_id' => 2, 'name' => 'صبغة شعر', 'category' => 'الشعر', 'description' => 'ألوان مختارة', 'duration_minutes' => 90, 'price' => 500, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['branch_id' => 1, 'name' => 'خدمة مخفية', 'category' => 'تجربة', 'description' => null, 'duration_minutes' => 10, 'price' => 1, 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

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

        $html = app(LandingPageController::class)->index(Request::create('/', 'GET'))->render();

        $this->assertStringContainsString('alert(1) صالة آمنة', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('--accent:#966b43', $html);
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
        $this->assertStringContainsString('قص شعر', $html);
        $this->assertStringNotContainsString('صبغة شعر', $html);
        $this->assertStringNotContainsString('خدمة مخفية', $html);
        $this->assertStringContainsString('2 صور', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('outside.jpg', $html);
        $this->assertStringNotContainsString('href=""', $html);
        $this->assertStringNotContainsString('id="offers"', $html);

        SystemConfig::set('landing_location_url', 'https://maps.app.goo.gl/example', 'landing');
        $mapHtml = app(LandingPageController::class)->index(Request::create('/', 'GET', ['branch_id' => 2]))->render();

        $this->assertStringContainsString('id="location"', $mapHtml);
        $this->assertStringContainsString('https://maps.app.goo.gl/example', $mapHtml);
        $this->assertStringContainsString('صبغة شعر', $mapHtml);
        $this->assertStringNotContainsString('قص شعر', $mapHtml);

        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('salon_services');
        Schema::dropIfExists('branches');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function tenantViewData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'صالون الاختبار',
            'shopName' => 'صالون الاختبار',
            'description' => 'خدمات عناية وجمال تناسبك.',
            'about' => 'صالون يقدم خدمات متخصصة للعناية والجمال.',
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
            'salonServices' => new Collection,
            'branches' => new Collection,
            'selectedBranchId' => 0,
            'pointsRate' => 10,
            'primaryCta' => 'شوف العروض',
            'primaryCtaUrl' => 'https://wa.me/201234567890?text=Booking',
            'secondaryCta' => 'افتح المنيو',
            'theme' => 'neon',
            'accentColor' => '',
            'secondaryAccentColor' => '',
            'backgroundColor' => '',
            'surfaceColor' => '',
            'textColor' => '',
            'heroLayout' => 'split',
            'sectionOrder' => 'services_experience_gallery',
            'visibility' => [
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
