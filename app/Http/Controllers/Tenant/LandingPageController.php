<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\RedeemCustomerPointsAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\SystemConfig;
use App\Scopes\BranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(Request $request): View
    {
        $shopName = $this->textSetting('sys_shop_name', 'صالون التجميل', 80);
        $title = $this->textSetting('landing_title', $shopName, 80);
        $description = $this->textSetting('landing_description', 'خدمات تجميل وعناية متخصصة، فريق من الخبراء، ومواعيد تناسبك.', 240);
        $about = $this->textSetting('landing_about', 'نقدم خدمات متكاملة للعناية بالشعر والبشرة والتجميل، مع فريق متخصص وتجربة مريحة تناسب احتياجاتك.', 600);
        $phone = $this->textSetting('landing_phone', '', 30);
        $whatsapp = preg_replace('/\D+/', '', $this->textSetting('landing_whatsapp', '', 20)) ?? '';
        $email = filter_var($this->textSetting('landing_email', '', 160), FILTER_VALIDATE_EMAIL) ?: '';
        $address = $this->textSetting('landing_address', '', 240);
        $openingHours = $this->textSetting('landing_opening_hours', '', 120);
        $locationUrl = $this->safeMapUrlSetting('landing_location_url');
        $mapEmbedUrl = $locationUrl !== ''
            ? 'https://www.google.com/maps?q='.rawurlencode($address !== '' ? $address : $shopName).'&output=embed'
            : '';
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $requestedBranchId = $request->integer('branch_id');
        $selectedBranch = $branches->firstWhere('id', $requestedBranchId) ?? $branches->first();
        $selectedBranchId = (int) ($selectedBranch?->id ?? 0);
        $salonServices = $selectedBranch
            ? SalonService::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $selectedBranchId)
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(9)
                ->get(['id', 'branch_id', 'name', 'category', 'description', 'duration_minutes', 'price'])
            : collect();
        $facebook = $this->safeUrlSetting('landing_facebook');
        $instagram = $this->safeUrlSetting('landing_instagram');
        $primaryCta = $this->textSetting('landing_primary_cta', 'احجز موعدك', 40);
        $secondaryCta = $this->textSetting('landing_secondary_cta', 'استكشف المنتجات', 40);
        $primaryCtaUrl = $branches->isNotEmpty()
            ? route('tenant.bookings.create', $selectedBranchId > 0 ? ['branch_id' => $selectedBranchId] : [])
            : ($whatsapp !== ''
                ? 'https://wa.me/'.$whatsapp.'?text='.urlencode("مرحبًا، أود الاستفسار عن حجز موعد في {$shopName}.")
                : ($phone !== '' ? "tel:{$phone}" : ''));
        $theme = $this->allowedSetting('landing_theme', ['neon', 'ember', 'electric', 'royal', 'forest', 'gold', 'crimson'], 'gold');
        $heroLayout = $this->allowedSetting('landing_hero_layout', ['split', 'immersive'], 'split');
        $sectionOrder = $this->allowedSetting('landing_section_order', [
            'services_experience_gallery',
            'experience_services_gallery',
            'gallery_services_experience',
        ], 'services_experience_gallery');
        $accentColor = $this->colorSetting('landing_accent_color');
        $secondaryAccentColor = $this->colorSetting('landing_secondary_accent_color');
        $backgroundColor = $this->colorSetting('landing_background_color');
        $surfaceColor = $this->colorSetting('landing_surface_color');
        $textColor = $this->colorSetting('landing_text_color');
        $logo = $this->uploadedAsset('landing_logo', 'landing-branding/');
        $heroImage = $this->uploadedAsset('landing_hero_image', 'landing-branding/');
        $gallery = $this->uploadedGallery();
        $services = $this->uploadedServices();
        $visibility = [
            'services' => $this->booleanSetting('landing_show_services', true),
            'experience' => $this->booleanSetting('landing_show_experience', true),
            'gallery' => $this->booleanSetting('landing_show_gallery', true),
            'about' => $this->booleanSetting('landing_show_about', true),
            'contact' => $this->booleanSetting('landing_show_contact', true),
            'loyalty' => $this->booleanSetting('landing_show_loyalty', true),
        ];

        // Points rate
        $pointsRate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', 10);
        if ($pointsRate <= 0) {
            $pointsRate = 10;
        }

        return view('tenant.landing', compact(
            'branches',
            'selectedBranchId',
            'salonServices',
            'shopName',
            'title',
            'description',
            'phone',
            'whatsapp',
            'email',
            'address',
            'openingHours',
            'locationUrl',
            'mapEmbedUrl',
            'facebook',
            'instagram',
            'about',
            'gallery',
            'services',
            'pointsRate',
            'primaryCta',
            'primaryCtaUrl',
            'secondaryCta',
            'theme',
            'accentColor',
            'secondaryAccentColor',
            'backgroundColor',
            'surfaceColor',
            'textColor',
            'heroLayout',
            'sectionOrder',
            'visibility',
            'logo',
            'heroImage',
        ));
    }

    private function textSetting(string $key, string $default, int $maxLength): string
    {
        $value = trim(strip_tags((string) SystemConfig::get($key, '')));

        return $value === '' ? $default : mb_substr($value, 0, $maxLength);
    }

    /** @param array<int, string> $allowed */
    private function allowedSetting(string $key, array $allowed, string $default): string
    {
        $value = (string) SystemConfig::get($key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $value = SystemConfig::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    private function safeUrlSetting(string $key): string
    {
        $value = trim((string) SystemConfig::get($key, ''));
        if (! filter_var($value, FILTER_VALIDATE_URL)) {
            return '';
        }

        return in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : '';
    }

    private function colorSetting(string $key): string
    {
        $value = trim((string) SystemConfig::get($key, ''));

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : '';
    }

    private function safeMapUrlSetting(string $key): string
    {
        $value = $this->safeUrlSetting($key);
        if ($value === '') {
            return '';
        }

        $host = strtolower((string) parse_url($value, PHP_URL_HOST));
        $path = strtolower((string) parse_url($value, PHP_URL_PATH));
        $isGoogleMap = in_array($host, ['google.com', 'www.google.com', 'maps.google.com'], true)
            && ($host === 'maps.google.com' || str_starts_with($path, '/maps'));
        $isLegacyGoogleMap = $host === 'goo.gl' && str_starts_with($path, '/maps');

        return $isGoogleMap || $isLegacyGoogleMap || in_array($host, ['maps.app.goo.gl', 'maps.apple.com', 'openstreetmap.org', 'www.openstreetmap.org'], true)
            ? $value
            : '';
    }

    private function uploadedAsset(string $key, string $directory): ?string
    {
        $path = trim((string) SystemConfig::get($key, ''));

        return str_starts_with($path, $directory) && Storage::disk('public')->exists($path) ? $path : null;
    }

    /** @return array<int, string> */
    private function uploadedGallery(): array
    {
        $decoded = json_decode((string) SystemConfig::get('landing_gallery', '[]'), true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($path): bool => is_string($path)
                && str_starts_with($path, 'landing-gallery/')
                && Storage::disk('public')->exists($path))
            ->take(8)
            ->values()
            ->all();
    }

    /** @return array<int, array{title: string, description: string, images: array<int, string>}> */
    private function uploadedServices(): array
    {
        $decoded = json_decode((string) SystemConfig::get('landing_services', '[]'), true);
        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($service): bool => is_array($service))
            ->map(function (array $service): ?array {
                $title = mb_substr(trim(strip_tags((string) ($service['title'] ?? ''))), 0, 60);
                $description = mb_substr(trim(strip_tags((string) ($service['description'] ?? ''))), 0, 160);
                $storedImages = $service['image'] ?? [];
                $storedImages = is_array($storedImages) ? $storedImages : [$storedImages];
                $images = collect($storedImages)
                    ->filter(fn ($image): bool => is_string($image)
                        && str_starts_with($image, 'landing-services/')
                        && Storage::disk('public')->exists($image))
                    ->take(6)
                    ->values()
                    ->all();

                if ($title === '' || $images === []) {
                    return null;
                }

                return compact('title', 'description', 'images');
            })
            ->filter()
            ->take(8)
            ->values()
            ->all();
    }

    public function menu(Request $request): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->get();

        $selectedBranchId = (int) $request->input('branch_id', $branches->first()?->id);

        $groups = InventoryGroup::where('is_active', true)
            ->where('name', '!=', 'مكونات')
            ->with(['items' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        // Calculate effective price per selected branch for each item
        foreach ($groups as $group) {
            foreach ($group->items as $item) {
                $item->effective_price = $item->getEffectiveSalePrice($selectedBranchId, 'direct_sale');
            }
        }

        $title = SystemConfig::get('landing_title', SystemConfig::get('sys_shop_name', 'صالون التجميل'));

        return view('tenant.menu', compact('groups', 'title', 'branches', 'selectedBranchId'));
    }

    public function convertPoints(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->back()->with('error', 'يجب عليك تسجيل الدخول أولاً.');
        }

        $request->validate([
            'points' => 'required|numeric|min:1',
        ], [
            'points.max' => 'النقاط المدخلة أكبر من رصيد نقاطك المتاحة.',
            'points.min' => 'يجب إدخال نقطة واحدة على الأقل.',
            'points.required' => 'يرجى تحديد عدد النقاط المراد استبدالها.',
        ]);

        $credit = app(RedeemCustomerPointsAction::class)->execute((int) $customer->id, (float) $request->input('points'));

        return redirect()->back()->with('success', "تم تحويل النقاط بنجاح إلى رصيد بقيمة {$credit->amount} جنيه!");
    }
}
