<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TenantActivationController;
use App\Http\Controllers\Api\TenantDeletionController;
use App\Http\Controllers\Api\TenantDomainCheckController;
use App\Http\Controllers\Api\TenantEntitlementsController;
use App\Http\Controllers\Api\TenantImpersonationController;
use App\Http\Controllers\Api\TenantProvisioningController;
use App\Http\Controllers\Api\TenantRenewalController;
use App\Http\Controllers\Api\TenantStatusController;
use App\Http\Controllers\Api\TenantSuspensionController;
use App\Http\Controllers\Api\TenantTrialDataResetController;
use Illuminate\Support\Facades\Route;

Route::match(['GET', 'POST'], '/tenants/check-domain', TenantDomainCheckController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.check-domain');

Route::post('/tenants', TenantProvisioningController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.store');

Route::delete('/tenants/{tenant}', TenantDeletionController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.destroy');

Route::post('/tenants/{tenant}/renew', TenantRenewalController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.renew');

Route::post('/tenants/{tenant}/reset-trial-data', TenantTrialDataResetController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.trial-data.reset');

Route::post('/tenants/{tenant}/suspend', TenantSuspensionController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.suspend');

Route::post('/tenants/{tenant}/activate', TenantActivationController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.activate');

Route::patch('/tenants/{tenant}/entitlements', TenantEntitlementsController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning', 'api.idempotency'])
    ->name('api.tenants.entitlements.update');

Route::get('/tenants/{tenantId}/status', TenantStatusController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.status');

Route::post('/tenants/{tenant}/impersonation', TenantImpersonationController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.impersonation.store');
