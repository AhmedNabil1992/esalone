<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tenants:run tenants:send-booking-alerts')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping();

Schedule::command('tenants:run tenants:send-session-expiry-alerts')
    ->everyMinute()
    ->runInBackground()
    ->withoutOverlapping();

Schedule::command('tenants:cleanup-expired')
    ->dailyAt('03:00')
    ->withoutOverlapping();
