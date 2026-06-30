<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Offer;
use App\Models\Tenant\SystemConfig;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View
    {
        // Fetch all active offers from database
        $offers = Offer::where('is_active', true)->get();

        // Retrieve landing configs
        $title = SystemConfig::get('landing_title', SystemConfig::get('sys_shop_name', 'صالة ألعاب بلاي ستيشن'));
        $description = SystemConfig::get('landing_description', 'المكان الأفضل لتجربة ألعاب الفيديو الاستثنائية وقضاء أوقات رائعة مع الأصدقاء.');
        $phone = SystemConfig::get('landing_phone', '');
        $whatsapp = SystemConfig::get('landing_whatsapp', '');
        $address = SystemConfig::get('landing_address', '');
        $facebook = SystemConfig::get('landing_facebook', '');
        $instagram = SystemConfig::get('landing_instagram', '');
        $about = SystemConfig::get('landing_about', 'نحن نوفر لك أفضل بيئة لعب مجهزة بأحدث أجهزة الجيل الجديد، شاشات فائقة الدقة 4K، ومقاعد مريحة لتجربة ترفيهية لا مثيل لها.');

        $galleryRaw = SystemConfig::get('landing_gallery', '[]');
        $gallery = json_decode($galleryRaw, true) ?: [];

        return view('tenant.landing', compact(
            'offers',
            'title',
            'description',
            'phone',
            'whatsapp',
            'address',
            'facebook',
            'instagram',
            'about',
            'gallery'
        ));
    }
}
