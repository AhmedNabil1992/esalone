<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\ImpersonateUserController;
use App\Http\Controllers\Tenant\LandingPageController;
use App\Http\Controllers\Tenant\PrintOrderPrepareController;
use App\Http\Controllers\Tenant\PrintPlayInvoiceController;
use App\Http\Controllers\Tenant\PrintShiftCloseController;
use App\Http\Controllers\Tenant\PublicBookingController;
use App\Http\Controllers\Tenant\PublicInvoiceController;
use App\Http\Controllers\Tenant\QuickOpenShiftController;
use App\Http\Middleware\EnsureTenantIsActive;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    EnsureTenantIsActive::class,
])->group(function () {
    Broadcast::routes();

    Route::get('/', [LandingPageController::class, 'index'])
        ->name('tenant.landing');

    Route::get('/bookings/create', [PublicBookingController::class, 'create'])
        ->name('tenant.bookings.create');

    Route::middleware('throttle:5,1')->post('/bookings', [PublicBookingController::class, 'store'])
        ->name('tenant.bookings.store');

    Route::get('/bookings/confirmation/{token}', [PublicBookingController::class, 'confirmation'])
        ->where('token', '[a-f0-9]{64}')
        ->name('tenant.bookings.confirmation');

    Route::middleware('throttle:5,1')->post('/bookings/confirmation/{token}/cancel', [PublicBookingController::class, 'cancel'])
        ->where('token', '[a-f0-9]{64}')
        ->name('tenant.bookings.cancel');

    Route::get('/menu', [LandingPageController::class, 'menu'])
        ->name('tenant.menu');

    Route::post('/convert-points', [LandingPageController::class, 'convertPoints'])
        ->name('tenant.customer.convert-points');

    Route::get('/invoice/view/{invoice}/{token}', PublicInvoiceController::class)
        ->name('tenant.invoices.public-view');

    Route::middleware('throttle:10,1')->get('/impersonate/{token}', ImpersonateUserController::class)
        ->name('tenant.impersonate');

    Route::middleware('auth')->get('/tenant/invoices/{invoice}/print', PrintPlayInvoiceController::class)
        ->name('tenant.invoices.print');

    Route::middleware('auth')->get('/tenant/order-prepares/print', [PrintOrderPrepareController::class, 'print'])
        ->name('tenant.order-prepares.print');

    Route::middleware('auth')->get('/tenant/shifts/{shift}/print-close', PrintShiftCloseController::class)
        ->name('tenant.shifts.print-close');

    Route::middleware('auth')->post('/tenant/shifts/quick-open', QuickOpenShiftController::class)
        ->name('tenant.shifts.quick-open');

});
