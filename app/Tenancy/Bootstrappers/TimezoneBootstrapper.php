<?php

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\Tenant;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use App\Models\Tenant\SystemConfig as Setting;
use Filament\Support\Facades\FilamentTimezone;
use Carbon\Carbon;

class TimezoneBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        // 1. نقرأ التوقيت من إعدادات قاعدة بيانات العميل
        $timezone = Setting::get('sys_system_time_zone', 'UTC');
        
        // 2. تغيير توقيت فيلامنت فقط (عشان الداتا بيز تفضل UTC)
        // FilamentTimezone::set($timezone);

        // 3. تسجيل الماكرو بتاع الـ Blade
        // Carbon::macro('tenantFormat', function (string $format) use ($timezone) {
        //     return $this->copy()->timezone($timezone)->format($format);
        // });
        date_default_timezone_set($timezone);
        
    }

    public function revert(): void
    {
        // إرجاع الإعدادات للوضع الافتراضي عند انتهاء الاتصال بالعميل
        FilamentTimezone::set('UTC');
    }
}