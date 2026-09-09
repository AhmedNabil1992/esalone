<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Facades\Tenancy;
use Throwable;

class GenerateTenantPermissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public string $tenantId,
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        // رفع مهلة التنفيذ لتوليد الصلاحيات (بعض الحالات قد تحتاج لأكثر من 30 ثانية)
        set_time_limit($this->timeout);

        try {
            Tenancy::initialize($tenant);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $exitCode = Artisan::call('shield:generate', [
                '--all' => true,
                '--option' => 'permissions',
                '--panel' => 'admin',
                '--no-interaction' => true,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException('Shield permission generation failed: '.Artisan::output());
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $superAdminRoleName = (string) config('filament-shield.super_admin.name', 'super_admin');
            $superAdminRole = Role::findOrCreate($superAdminRoleName, 'web');
            $permissionNames = Permission::query()->where('guard_name', 'web')->pluck('name')->all();

            if ($permissionNames === []) {
                throw new RuntimeException('Shield generated no web permissions: '.Artisan::output());
            }

            $superAdminRole->syncPermissions($permissionNames);

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (Throwable $throwable) {
            Log::error('Failed generating tenant permissions.', [
                'tenant_id' => $this->tenantId,
                'error' => $throwable->getMessage(),
            ]);

            throw $throwable;
        } finally {
            Tenancy::end();
        }
    }
}
