<?php

use Illuminate\Support\Facades\Route;

foreach (config('tenancy.central_domains', []) as $centralDomain) {
    Route::domain($centralDomain)->group(function () use ($centralDomain) {
        $landingRoute = Route::view('/', 'landing');

        if ($centralDomain === config('tenancy.central_domains.0')) {
            $landingRoute->name('home');
        }

        Route::fallback(function () {
            abort(404);
        });
    });
}
