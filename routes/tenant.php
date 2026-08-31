<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\LandingPageController;
use App\Http\Controllers\Tenant\PrintOrderPrepareController;
use App\Http\Controllers\Tenant\PrintPlayInvoiceController;
use App\Http\Controllers\Tenant\PublicInvoiceController;
use App\Models\Tenant\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
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
])->group(function () {
    Broadcast::routes();

    Route::get('/', [LandingPageController::class, 'index'])
        ->name('tenant.landing');

    Route::get('/menu', [LandingPageController::class, 'menu'])
        ->name('tenant.menu');

    Route::post('/convert-points', [LandingPageController::class, 'convertPoints'])
        ->name('tenant.customer.convert-points');

    Route::get('/invoice/view/{invoice}/{token}', PublicInvoiceController::class)
        ->name('tenant.invoices.public-view');

    Route::middleware('auth')->get('/tenant/invoices/{invoice}/print', PrintPlayInvoiceController::class)
        ->name('tenant.invoices.print');

    Route::middleware('auth')->get('/tenant/order-prepares/print', [PrintOrderPrepareController::class, 'print'])
        ->name('tenant.order-prepares.print');

    Route::middleware('auth')->get('/tenant/shifts/quick-open', function () {
        if (! Shift::currentOpenShift()) {
            DB::transaction(function (): void {
                $lastClosedShift = Shift::query()
                    ->where('status', 'close')
                    ->orderByDesc('shift_no')
                    ->first();
                $startAmount = $lastClosedShift ? (float) $lastClosedShift->actual_amount : 0.0;

                Shift::query()->create([
                    'shift_no' => Shift::nextShiftNumber(),
                    'opened_by_user_id' => Auth::id(),
                    'shift_date' => now()->toDateString(),
                    'shift_from' => now()->toDateTimeString(),
                    'start_amount' => $startAmount,
                    'actual_amount' => 0,
                    'status' => 'open',
                    'is_active' => true,
                    'notify' => false,
                ]);
            });
        }

        $redirectTo = request()->query('redirect');

        if (is_string($redirectTo) && filled($redirectTo)) {
            return redirect()->to($redirectTo);
        }

        return redirect()->route('filament.admin.resources.tenant.purchases.create');
    })->name('tenant.shifts.quick-open');

});
