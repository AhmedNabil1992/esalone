<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreTenantRequest;
use App\Models\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class TenantProvisioningController extends Controller
{
    public function __invoke(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->validated();

        $tenant = null;
        $tenantId = (string) Str::ulid();

        try {
            $tenant = Tenant::create([
                'id' => $tenantId,
            ]);

            $tenant->domains()->create([
                'domain' => $data['domain'],
            ]);

            Tenancy::initialize($tenant);

            if (! tenancy()->initialized || ! tenancy()->tenant || tenancy()->tenant->getTenantKey() !== $tenant->getTenantKey()) {
                throw new RuntimeException('Tenancy context is not initialized for the target tenant.');
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $superAdminRoleName = (string) config('filament-shield.super_admin.name', 'super_admin');
            $superAdminRole = Role::findOrCreate($superAdminRoleName, 'web');

            Artisan::call('shield:generate', [
                '--all' => true,
                '--option' => 'permissions',
                '--panel' => 'admin',
                '--no-interaction' => true,
                '--silent' => true,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $permissionNames = Permission::query()->pluck('name')->all();

            if ($permissionNames === []) {
                throw new RuntimeException('No permissions were generated for the tenant.');
            }

            $superAdminRole->syncPermissions($permissionNames);

            $tenantUser = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('12345678'),
            ]);

            $tenantUser->syncRoles([$superAdminRole]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (! $tenantUser->fresh()->hasRole($superAdminRoleName, 'web')) {
                throw new RuntimeException('Failed to assign super_admin role to tenant user.');
            }

            Tenancy::end();

            return response()->json([
                'message' => 'Tenant created successfully.',
                'tenant_id' => $tenant->getTenantKey(),
                'domain' => $data['domain'],
                'user_email' => $tenantUser->email,
            ], 201);
        } catch (Throwable $throwable) {
            Tenancy::end();

            if ($tenant) {
                $tenant->delete();
            }

            report($throwable);

            return response()->json([
                'message' => 'Failed to provision tenant.',
                'error' => $throwable->getMessage(),
            ], 500);
        }
    }
}
