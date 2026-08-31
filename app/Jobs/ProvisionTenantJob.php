<?php

namespace App\Jobs;

use App\Mail\TenantProvisionedMail;
use App\Models\Tenant;
use App\Models\Tenant\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class ProvisionTenantJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public array $data,
    ) {
        $this->onConnection('database');
        $this->onQueue('tenant-provisioning');
    }

    public function handle(): void
    {
        $tenant = null;
        $tenantId = (string) Str::ulid();

        try {
            $tenant = Tenant::create([
                'id' => $tenantId,
                'started_at' => $this->data['started_at'] ?? null,
                'ended_at' => $this->data['ended_at'] ?? null,
                'user_id' => $this->data['user_id'] ?? null,
                'auto_renew' => $this->data['auto_renew'] ?? null,
                'branches_limit' => $this->data['branches_limit'] ?? 1,
                'has_ai_subscription' => $this->data['has_ai_subscription'] ?? true,
                'ai_requests_limit' => $this->data['ai_requests_limit'] ?? 10,
            ]);

            $tenant->domains()->create([
                'domain' => $this->data['domain'],
            ]);

            Tenancy::initialize($tenant);

            if (! tenancy()->initialized || ! tenancy()->tenant || tenancy()->tenant->getTenantKey() !== $tenant->getTenantKey()) {
                throw new RuntimeException('Tenancy context is not initialized for the target tenant.');
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $superAdminRoleName = (string) config('filament-shield.super_admin.name', 'super_admin');
            $superAdminRole = Role::findOrCreate($superAdminRoleName, 'web');

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $permissionNames = Permission::query()->pluck('name')->all();

            if ($permissionNames !== []) {
                $superAdminRole->syncPermissions($permissionNames);
            }

            $adminUser = User::query()->create([
                'name' => 'System Admin',
                'email' => 'admin@etech-valley.com',
                'password' => Hash::make('Whocandoitnow157#'),
            ]);

            $adminUser->syncRoles([$superAdminRole]);

            $tenantUser = User::query()->create([
                'name' => $this->data['name'],
                'email' => $this->data['email'],
                'password' => Hash::make('12345678'),
            ]);

            $tenantUser->syncRoles([$superAdminRole]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            if (! $tenantUser->fresh()->hasRole($superAdminRoleName, 'web')) {
                throw new RuntimeException('Failed to assign super_admin role to tenant user.');
            }

            Tenancy::end();

            // Run generate permissions in background
            GenerateTenantPermissionsJob::dispatch($tenant->getTenantKey())
                ->onConnection('database')
                ->onQueue('tenant-provisioning');

            // Send Mail to customer
            Mail::to($this->data['email'])->send(
                new TenantProvisionedMail($this->data['name'], $this->data['domain'])
            );

        } catch (Throwable $throwable) {
            Tenancy::end();

            if ($tenant) {
                $tenant->delete();
            }

            report($throwable);
            throw $throwable;
        }
    }
}
