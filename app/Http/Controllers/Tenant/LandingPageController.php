<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\Offer;
use App\Models\Tenant\SystemConfig;
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
        $pointsRate = (float) SystemConfig::get('sys_customer_points_ratio', 10);
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

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('customer')->attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();

            return redirect()->back()->with('success', 'تم تسجيل الدخول بنجاح!');
        }

        return redirect()->back()->withErrors([
            'login_error' => 'رقم الهاتف أو كلمة المرور غير صحيحة.',
        ])->withInput($request->only('phone'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:customers,phone|max:20',
            'password' => 'required|string|min:6|confirmed',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
        ], [
            'phone.unique' => 'رقم الهاتف هذا مسجل بالفعل لدينا.',
            'password.confirmed' => 'تأكيد كلمة المرور غير متطابق.',
            'password.min' => 'كلمة المرور يجب ألا تقل عن 6 أحرف.',
        ]);

        $customer = Customer::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => $data['password'],
            'is_active' => true,
            'balance' => 0,
            'points' => 0,
        ]);

        Auth::guard('customer')->login($customer);

        return redirect()->back()->with('success', 'تم إنشاء حسابك وتسجيل الدخول بنجاح!');
    }

    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->back()->with('success', 'تم تسجيل الخروج بنجاح.');
    }

    public function convertPoints(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        if (! $customer) {
            return redirect()->back()->with('error', 'يجب عليك تسجيل الدخول أولاً.');
        }

        $points = (float) $customer->points;
        if ($points <= 0) {
            return redirect()->back()->with('error', 'ليس لديك نقاط كافية للتحويل حالياً.');
        }

        $rate = (float) SystemConfig::get('sys_customer_points_ratio', 10);
        if ($rate <= 0) {
            $rate = 10;
        }

        $moneyToAdd = round($points / $rate, 2);

        DB::transaction(function () use ($customer, $points, $moneyToAdd) {
            $balanceBefore = (float) $customer->balance;
            $balanceAfter = $balanceBefore + $moneyToAdd;

            // Update customer balance & points
            $customer->points = 0;
            $customer->balance = $balanceAfter;
            $customer->save();

            // Record transaction history under customer credits
            CustomerCredit::create([
                'customer_id' => $customer->id,
                'user_id' => null,
                'type' => 'deposit',
                'amount' => $moneyToAdd,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'تحويل نقاط الولاء إلى رصيد ('.$points.' نقطة)',
            ]);
        });

        return redirect()->back()->with('success', "تم تحويل {$points} نقطة بنجاح إلى رصيد بقيمة {$moneyToAdd} جنيه!");
    }
}
