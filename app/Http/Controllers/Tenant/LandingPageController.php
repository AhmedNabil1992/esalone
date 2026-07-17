<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Offer;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

    public function menu(): View
    {
        $groups = InventoryGroup::where('is_active', true)
            ->where('name', '!=', 'مكونات')
            ->with(['items' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $title = SystemConfig::get('landing_title', SystemConfig::get('sys_shop_name', 'صالة ألعاب بلاي ستيشن'));

        return view('tenant.menu', compact('groups', 'title'));
    }

    public function convertPoints(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->back()->with('error', 'يجب عليك تسجيل الدخول أولاً.');
        }

        $request->validate([
            'points' => 'required|numeric|min:1|max:'.(float) $customer->points,
        ], [
            'points.max' => 'النقاط المدخلة أكبر من رصيد نقاطك المتاحة.',
            'points.min' => 'يجب إدخال نقطة واحدة على الأقل.',
            'points.required' => 'يرجى تحديد عدد النقاط المراد استبدالها.',
        ]);

        $pointsToRedeem = (float) $request->input('points');

        $rate = (float) SystemConfig::get('sys_customer_points_redeem_ratio', 10);
        if ($rate <= 0) {
            $rate = 10;
        }

        $convertiblePoints = floor($pointsToRedeem / $rate) * $rate;

        if ($convertiblePoints <= 0) {
            return redirect()->back()->with('error', "يجب استبدال عدد نقاط من مضاعفات الـ {$rate} (بحد أدنى {$rate} نقطة).");
        }

        $moneyToAdd = round($convertiblePoints / $rate, 2);

        DB::transaction(function () use ($customer, $convertiblePoints, $moneyToAdd) {
            $balanceBefore = (float) $customer->balance;
            $balanceAfter = $balanceBefore + $moneyToAdd;

            // Update customer balance & points
            $customer->points = (float) $customer->points - $convertiblePoints;
            $customer->balance = $balanceAfter;
            $customer->save();

            // Record transaction history under customer credits
            CustomerCredit::create([
                'customer_id' => $customer->id,
                'user_id' => auth()->id() ?? User::first()?->id,
                'type' => 'redeem',
                'amount' => $moneyToAdd,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'تحويل نقاط الولاء إلى رصيد ('.$convertiblePoints.' نقطة) عبر البوابة الإلكترونية',
            ]);
        });

        return redirect()->back()->with('success', "تم تحويل {$convertiblePoints} نقطة بنجاح إلى رصيد بقيمة {$moneyToAdd} جنيه!");
    }
}
