<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant\SystemConfig as Setting;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Support\Facades\FilamentTimezone;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next)
    {
        try {
            // بنقرأ التوقيت من إعدادات العميل، ولو مش موجود بناخد التوقيت الافتراضي
            $timezone = Setting::get('sys_system_time_zone', config('app.timezone'));

            if ($timezone) {
                // 1. تظبيط توقيت لاراڤيل وكاربون الأساسي (عشان الحسابات في الباك إند)
                // config(['app.timezone' => $timezone]);
                // date_default_timezone_set($timezone);
                // Carbon::setFallbackTimezone($timezone);

                // 2. السحر بتاع فيلامنت: تظبيط التوقيت جلوبال لكل الجداول والفورمز
                FilamentTimezone::set($timezone);
                
            }
        } catch (Throwable) {
            // لو حصل أي مشكلة (مثلاً الجدول لسه متعملوش ميكريشن)، نتجاهل الخطأ ويكمل بالتوقيت الافتراضي
        }

        return $next($request);
    }
}