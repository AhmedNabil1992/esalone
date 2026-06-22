<?php

namespace App\Console;

use App\Console\Commands\CleanupExpiredTenants;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // تشغيل تنظيف بيانات الـ tenants المنتهية يوميًا في الساعة 2 صباحًا
        $schedule->command(CleanupExpiredTenants::class)->dailyAt('02:00');

        // اختياري: يمكن تشغيله كل ساعة للفحص الأكثر تكرارًا
        // $schedule->command(CleanupExpiredTenants::class)->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
