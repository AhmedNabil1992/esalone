<?php

declare(strict_types=1);

use App\Http\Controllers\Api\TenantDeletionController;
use App\Http\Controllers\Api\TenantProvisioningController;
use Illuminate\Support\Facades\Route;

Route::post('/tenants', TenantProvisioningController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.store');

Route::delete('/tenants/{tenant}', TenantDeletionController::class)
    ->middleware(['tenant.provisioning.token', 'throttle:tenant-provisioning'])
    ->name('api.tenants.destroy');
