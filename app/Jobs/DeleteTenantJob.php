<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantProvisioningRequest;
use App\Services\TenantWebhookService;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(public string $tenantId)
    {
        $this->onConnection('database');
        $this->onQueue('tenant-provisioning');
    }

    public function handle(TenantLifecycleLock $lock, TenantWebhookService $webhooks): void
    {
        $lock->run($this->tenantId, function () use ($webhooks): void {
            $tenant = Tenant::query()->find($this->tenantId);
            if (! $tenant) {
                return;
            }

            $externalSubscriptionId = $tenant->external_subscription_id;
            $data = [
                'tenant_id' => $tenant->getTenantKey(),
                'provisioning_request_id' => $tenant->provisioning_request_id,
                'user_id' => $tenant->user_id,
                'domain' => $tenant->domains()->value('domain'),
            ];

            $tenant->delete();
            TenantProvisioningRequest::query()
                ->where('tenant_id', $this->tenantId)
                ->update(['status' => 'deleted', 'completed_at' => now()]);

            if ($externalSubscriptionId) {
                $webhooks->dispatch('tenant.deleted', $externalSubscriptionId, $data);
            }
        });
    }
}
