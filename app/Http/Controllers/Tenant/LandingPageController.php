<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\RedeemCustomerPointsAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Offer;
use App\Models\Tenant\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Points rate
        $pointsRate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', 10);
        if ($pointsRate <= 0) {
            $pointsRate = 10;
        }

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
            'gallery',
            'pointsRate'
        ));
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

        $title = SystemConfig::get('landing_title', SystemConfig::get('sys_shop_name', 'صالة ألعاب بلاي ستيشن'));

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
