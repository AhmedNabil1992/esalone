<?php

namespace App\Tenancy\Bootstrappers;

use App\Models\Tenant\SystemConfig as Setting;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentTimezone;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class TimezoneBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        $timezone = 'UTC';

        try {
            // 1. نقرأ التوقيت من إعدادات قاعدة بيانات العميل
            $timezone = Setting::get('sys_system_time_zone', 'UTC');
        } catch (\Throwable $e) {
            // Table doesn't exist yet (e.g. during migrations / tenant provisioning)
        }

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
