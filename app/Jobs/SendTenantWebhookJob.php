<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TenantWebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SendTenantWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 6;

    public int $timeout = 30;

    public function __construct(public int $deliveryId)
    {
        $this->onConnection('database');
        $this->onQueue('tenant-provisioning');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300, 900];
    }

    /**
     * @throws ConnectionException
     */
    public function handle(): void
    {
        $delivery = TenantWebhookDelivery::query()->findOrFail($this->deliveryId);
        if ($delivery->status === 'sent') {
            return;
        }

        $baseUrl = rtrim((string) config('services.tenant_provisioning.webhook.base_url'), '/');
        $path = ltrim((string) config('services.tenant_provisioning.webhook.path'), '/');
        $secret = (string) config('services.tenant_provisioning.webhook.secret');

        if ($baseUrl === '' || $path === '' || $secret === '') {
            $delivery->update([
                'status' => 'failed',
                'last_error' => 'Webhook destination or signing secret is not configured.',
            ]);

            return;
        }

        $rawBody = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->utc()->timestamp;
        $signature = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$rawBody, $secret);

        $delivery->increment('attempts');
        $response = Http::acceptJson()
            ->withHeaders([
                'X-Online-System' => (string) config('services.tenant_provisioning.webhook.system', 'ps-web'),
                'X-Webhook-Timestamp' => $timestamp,
                'X-Webhook-Signature' => $signature,
            ])
            ->withBody($rawBody, 'application/json')
            ->timeout(15)
            ->post($baseUrl.'/'.$path);

        $delivery->update([
            'last_response_status' => $response->status(),
            'last_error' => $response->successful() ? null : 'Webhook endpoint returned HTTP '.$response->status().'.',
        ]);

        if ($response->successful()) {
            $delivery->update(['status' => 'sent', 'sent_at' => now()]);

            return;
        }

        if ($response->status() === 401) {
            $delivery->update(['status' => 'failed']);

            return;
        }

        if ($response->status() === 429) {
            $retryAfterHeader = $response->header('Retry-After');
            $retryAfter = $retryAfterHeader !== '' ? max(1, (int) $retryAfterHeader) : 60;
            $this->release($retryAfter);

            return;
        }

        if ($response->serverError()) {
            throw new RuntimeException('Tenant webhook delivery failed with a server error.');
        }

        $delivery->update(['status' => 'failed']);
    }

    public function failed(?Throwable $throwable): void
    {
        TenantWebhookDelivery::query()->whereKey($this->deliveryId)->update([
            'status' => 'failed',
            'last_error' => $throwable?->getMessage(),
        ]);
    }
}
