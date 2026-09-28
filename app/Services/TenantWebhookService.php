<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendTenantWebhookJob;
use App\Models\Tenant;
use App\Models\TenantWebhookDelivery;
use Illuminate\Support\Str;

class TenantWebhookService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function dispatch(string $eventType, string $externalSubscriptionId, array $data, ?Tenant $tenant = null): TenantWebhookDelivery
    {
        $payload = [
            'event_id' => (string) Str::ulid(),
            'event_type' => $eventType,
            'occurred_at' => now()->utc()->toIso8601String(),
            'data' => array_merge([
                'tenant_id' => $tenant?->getTenantKey(),
                'external_subscription_id' => $externalSubscriptionId,
            ], $data),
        ];

        $delivery = TenantWebhookDelivery::query()->create([
            'event_id' => $payload['event_id'],
            'event_type' => $eventType,
            'tenant_id' => $tenant?->getTenantKey(),
            'external_subscription_id' => $externalSubscriptionId,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        SendTenantWebhookJob::dispatch($delivery->id)->afterCommit();

        return $delivery;
    }
}
