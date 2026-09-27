<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TenantDeletionController;
use App\Http\Controllers\Api\TenantDomainCheckController;
use App\Http\Controllers\Api\TenantEntitlementsController;
use App\Http\Controllers\Api\TenantImpersonationController;
use App\Http\Controllers\Api\TenantProvisioningController;
use App\Http\Controllers\Api\TenantRenewalController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/tenants/check-domain', TenantDomainCheckController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.check-domain');

Route::post('/tenants', TenantProvisioningController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.store');

Route::delete('/tenants/{tenant}', TenantDeletionController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.destroy');

Route::post('/tenants/{tenant}/renew', TenantRenewalController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.renew');

Route::patch('/tenants/{tenant}/entitlements', TenantEntitlementsController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.entitlements.update');

Route::post('/tenants/{tenant}/impersonation', TenantImpersonationController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.impersonation.store');
