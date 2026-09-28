<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\TenantProvisionedMail;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Models\TenantProvisioningRequest;
use App\Services\TenantWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
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

    /** @param array<string, mixed> $data */
    public function __construct(public array $data)
    {
        $this->onConnection('database');
        $this->onQueue('tenant-provisioning');
    }

    public function handle(?TenantWebhookService $webhooks = null): void
    {
        $webhooks ??= app(TenantWebhookService::class);
        $tenant = null;
        $tenantId = (string) Str::ulid();
        $externalSubscriptionId = isset($this->data['external_subscription_id'])
            ? (string) $this->data['external_subscription_id']
            : null;
        $provisioningRequestId = isset($this->data['provisioning_request_id'])
            ? (string) $this->data['provisioning_request_id']
            : null;

        try {
            $shouldDispatchStarted = $provisioningRequestId !== null
                && TenantProvisioningRequest::query()
                    ->whereKey($provisioningRequestId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'provisioning',
                        'error_code' => null,
                        'error_message' => null,
                        'retryable' => false,
                    ]) === 1;

            if ($externalSubscriptionId !== null && $shouldDispatchStarted) {
                $webhooks->dispatch('tenant.provisioning_started', $externalSubscriptionId, [
                    'provisioning_request_id' => $provisioningRequestId,
                    'user_id' => isset($this->data['user_id']) ? (int) $this->data['user_id'] : null,
                    'domain' => $this->data['domain'] ?? null,
                ]);
            }

            $tenant = new Tenant([
                'id' => $tenantId,
                'external_subscription_id' => $externalSubscriptionId,
                'provisioning_request_id' => $provisioningRequestId,
                'provisioning_status' => 'provisioning',
                'started_at' => $this->data['started_at'] ?? null,
                'ended_at' => $this->data['ended_at'] ?? null,
                'user_id' => $this->data['user_id'] ?? null,
                'auto_renew' => $this->data['auto_renew'] ?? null,
                'branches_limit' => $this->data['branches_limit'] ?? 1,
                'has_ai_subscription' => $this->data['has_ai_subscription'] ?? true,
                'ai_requests_limit' => $this->data['ai_requests_limit'] ?? 10,
                'is_active' => true,
            ]);
            $tenant->save();

            $tenant->domains()->create(['domain' => $this->data['domain']]);

            Tenancy::initialize($tenant);

            if (! tenancy()->initialized || tenant('id') !== $tenant->getTenantKey()) {
                throw new RuntimeException('Tenancy context is not initialized for the target tenant.');
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $superAdminRoleName = (string) config('filament-shield.super_admin.name', 'super_admin');
            $superAdminRole = Role::findOrCreate($superAdminRoleName, 'web');
            $permissionNames = Permission::query()->pluck('name')->all();

            if ($permissionNames !== []) {
                $superAdminRole->syncPermissions($permissionNames);
            }

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
            app()->call([new GenerateTenantPermissionsJob((string) $tenant->getTenantKey()), 'handle']);

            DB::transaction(function () use ($tenant, $provisioningRequestId): void {
                $tenant->update([
                    'provisioning_status' => 'ready',
                    'completed_at' => now(),
                    'error_code' => null,
                    'error_message' => null,
                    'retryable' => false,
                ]);

                $this->updateProvisioningRequest($provisioningRequestId, [
                    'status' => 'ready',
                    'tenant_id' => $tenant->getTenantKey(),
                    'completed_at' => now(),
                ]);
            });

            if ($externalSubscriptionId !== null) {
                $webhooks->dispatch('tenant.provisioned', $externalSubscriptionId, [
                    'provisioning_request_id' => $provisioningRequestId,
                    'user_id' => (int) $this->data['user_id'],
                    'domain' => (string) $this->data['domain'],
                    'login_url' => 'https://'.$this->data['domain'].'/admin/login',
                    'started_at' => $tenant->started_at?->utc()->toIso8601String(),
                    'ended_at' => $tenant->ended_at?->utc()->toIso8601String(),
                    'is_active' => true,
                    'error_code' => null,
                    'error_message' => null,
                ], $tenant);
            }
        } catch (Throwable $throwable) {
            Tenancy::end();

            if ($tenant?->exists) {
                try {
                    $tenant->delete();
                } catch (Throwable $cleanupFailure) {
                    report($cleanupFailure);
                }
            }

            report($throwable);
            throw $throwable;
        }

        try {
            Mail::to($this->data['email'])->queue(
                (new TenantProvisionedMail($this->data['name'], $this->data['domain']))
                    ->onConnection('database')
                    ->onQueue('default')
            );
        } catch (Throwable $throwable) {
            report($throwable);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function updateProvisioningRequest(?string $requestId, array $attributes): void
    {
        if ($requestId === null) {
            return;
        }

        TenantProvisioningRequest::query()->whereKey($requestId)->update($attributes);
    }

    public function failed(?Throwable $throwable): void
    {
        $provisioningRequestId = isset($this->data['provisioning_request_id'])
            ? (string) $this->data['provisioning_request_id']
            : null;
        $externalSubscriptionId = isset($this->data['external_subscription_id'])
            ? (string) $this->data['external_subscription_id']
            : null;
        $message = $throwable?->getMessage() ?? 'Tenant provisioning failed.';

        $this->updateProvisioningRequest($provisioningRequestId, [
            'status' => 'failed',
            'error_code' => 'PROVISIONING_FAILED',
            'error_message' => $message,
            'retryable' => true,
            'completed_at' => now(),
        ]);

        if ($externalSubscriptionId !== null) {
            app(TenantWebhookService::class)->dispatch('tenant.provisioning_failed', $externalSubscriptionId, [
                'provisioning_request_id' => $provisioningRequestId,
                'user_id' => isset($this->data['user_id']) ? (int) $this->data['user_id'] : null,
                'domain' => $this->data['domain'] ?? null,
                'error_code' => 'PROVISIONING_FAILED',
                'error_message' => $message,
                'retryable' => true,
            ]);
        }
    }
}
