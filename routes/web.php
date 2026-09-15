<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains', []) as $centralDomain) {
    Route::domain($centralDomain)->group(function () use ($centralDomain) {
        $landingRoute = Route::view('/', 'landing');

        Route::redirect('/dashboard', '/admin')->name('dashboard');

        require __DIR__.'/settings.php';

        if ($centralDomain === config('tenancy.central_domains.0')) {
            $landingRoute->name('home');
        }

        Route::fallback(function () {
            abort(404);
        });
    });
}

// Health check يصلح للمراقبة الخارجية
Route::get('/health', function () {
    $dbStatus = 'ok';

    try {
        DB::connection()->getPdo();
    } catch (Throwable $e) {
        $dbStatus = 'error';
    }

    $status = [
        'app' => 'ok',
        'db' => $dbStatus,
    ];

    return response()->json($status, $dbStatus === 'ok' ? 200 : 500);
})->name('health');
