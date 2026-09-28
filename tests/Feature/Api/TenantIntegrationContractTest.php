<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\ProvisionTenantJob;
use App\Jobs\SendTenantWebhookJob;
use App\Models\Tenant;
use App\Models\TenantProvisioningRequest;
use App\Models\TenantWebhookDelivery;
use App\Services\TenantWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TenantIntegrationContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.tenant_provisioning.token', 'integration-token');
        config()->set('services.tenant_provisioning.webhook.base_url', 'https://aureuserp.test');
        config()->set('services.tenant_provisioning.webhook.path', 'api/webhooks/ps-web/tenant-events');
        config()->set('services.tenant_provisioning.webhook.secret', 'webhook-secret');
    }

    public function test_create_request_is_idempotent_and_rejects_changed_payload(): void
    {
        Queue::fake();
        $payload = $this->provisioningPayload();

        $first = $this->withHeaders(['Idempotency-Key' => 'provision:481'])
            ->withToken('integration-token')
            ->postJson('/api/tenants', $payload)
            ->assertAccepted();

        $this->withHeaders(['Idempotency-Key' => 'provision:481'])
            ->withToken('integration-token')
            ->postJson('/api/tenants', $payload)
            ->assertAccepted()
            ->assertHeader('X-Idempotent-Replay', 'true')
            ->assertExactJson($first->json());

        $this->withHeaders(['Idempotency-Key' => 'provision:481'])
            ->withToken('integration-token')
            ->postJson('/api/tenants', array_replace($payload, ['name' => 'Changed Name']))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'IDEMPOTENCY_KEY_REUSED');

        $this->withHeaders(['Idempotency-Key' => 'provision:same-subscription'])
            ->withToken('integration-token')
            ->postJson('/api/tenants', array_replace($payload, ['domain' => 'another.ps-web.example']))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'SUBSCRIPTION_ALREADY_PROVISIONED');

        $this->withHeaders(['Idempotency-Key' => 'provision:same-domain'])
            ->withToken('integration-token')
            ->postJson('/api/tenants', array_replace($payload, ['external_subscription_id' => 'online-instance:999']))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'DOMAIN_ALREADY_TAKEN');

        $this->assertSame(1, TenantProvisioningRequest::query()->count());
        Queue::assertPushed(ProvisionTenantJob::class, 1);
    }

    public function test_status_returns_external_reference_and_exact_utc_datetimes(): void
    {
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'external_subscription_id' => 'online-instance:481',
            'provisioning_request_id' => (string) Str::ulid(),
            'provisioning_status' => 'ready',
            'started_at' => '2026-09-27T10:00:00Z',
            'ended_at' => '2026-10-27T10:00:00Z',
            'is_active' => true,
            'completed_at' => '2026-09-27T10:03:12Z',
        ]));
        $tenant->domains()->create(['domain' => 'client-name.ps-web.example']);

        $this->withToken('integration-token')
            ->getJson('/api/tenants/'.$tenant->id.'/status')
            ->assertOk()
            ->assertJsonPath('external_subscription_id', 'online-instance:481')
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('domain', 'client-name.ps-web.example')
            ->assertJsonPath('ended_at', '2026-10-27T10:00:00+00:00');
    }

    public function test_status_keeps_a_deleted_tenant_tombstone(): void
    {
        $tenantId = (string) Str::ulid();
        TenantProvisioningRequest::query()->create([
            'id' => (string) Str::ulid(),
            'external_subscription_id' => 'online-instance:deleted',
            'domain' => 'deleted.ps-web.example',
            'user_id' => 105,
            'name' => 'Deleted Client',
            'email' => 'deleted@example.com',
            'payload' => $this->provisioningPayload(),
            'status' => 'deleted',
            'tenant_id' => $tenantId,
            'completed_at' => now(),
        ]);

        $this->withToken('integration-token')
            ->getJson('/api/tenants/'.$tenantId.'/status')
            ->assertOk()
            ->assertJsonPath('tenant_id', $tenantId)
            ->assertJsonPath('external_subscription_id', 'online-instance:deleted')
            ->assertJsonPath('status', 'deleted')
            ->assertJsonPath('login_url', null);
    }

    public function test_tenant_can_be_suspended_and_activated_idempotently(): void
    {
        Queue::fake();
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'external_subscription_id' => 'online-instance:482',
            'provisioning_request_id' => (string) Str::ulid(),
            'provisioning_status' => 'ready',
            'ended_at' => now()->addMonth(),
            'is_active' => true,
        ]));

        $suspension = $this->withHeaders(['Idempotency-Key' => 'suspend:482'])
            ->withToken('integration-token')
            ->postJson('/api/tenants/'.$tenant->id.'/suspend')
            ->assertOk()
            ->assertJsonPath('status', 'suspended')
            ->assertJsonPath('is_active', false);

        $this->withHeaders(['Idempotency-Key' => 'suspend:482'])
            ->withToken('integration-token')
            ->postJson('/api/tenants/'.$tenant->id.'/suspend')
            ->assertOk()
            ->assertHeader('X-Idempotent-Replay', 'true')
            ->assertExactJson($suspension->json());

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'provisioning_status' => 'suspended',
            'is_active' => false,
        ]);
        $this->assertSame(1, TenantWebhookDelivery::query()->where('event_type', 'tenant.suspended')->count());

        $this->withHeaders(['Idempotency-Key' => 'activate:482'])
            ->withToken('integration-token')
            ->postJson('/api/tenants/'.$tenant->id.'/activate')
            ->assertOk()
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('is_active', true);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'provisioning_status' => 'ready',
            'is_active' => true,
        ]);
        $this->assertSame(1, TenantWebhookDelivery::query()->where('event_type', 'tenant.activated')->count());
    }

    public function test_expired_tenant_cannot_be_activated_without_renewal(): void
    {
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::query()->create([
            'id' => (string) Str::ulid(),
            'external_subscription_id' => 'online-instance:483',
            'provisioning_status' => 'suspended',
            'ended_at' => now()->subMinute(),
            'is_active' => false,
        ]));

        $this->withToken('integration-token')
            ->postJson('/api/tenants/'.$tenant->id.'/activate')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'SUBSCRIPTION_EXPIRED');

        $this->assertFalse((bool) $tenant->refresh()->is_active);
    }

    public function test_webhook_retries_with_same_event_id_and_valid_raw_body_signature(): void
    {
        Queue::fake();
        $delivery = app(TenantWebhookService::class)->dispatch(
            'tenant.provisioning_failed',
            'online-instance:481',
            ['error_code' => 'PROVISIONING_FAILED', 'retryable' => true],
        );
        $eventId = $delivery->event_id;

        Http::fakeSequence()
            ->push('temporary failure', 500)
            ->push(['processed' => true], 200);

        $job = new SendTenantWebhookJob($delivery->id);
        try {
            $job->handle();
            $this->fail('Expected the first webhook attempt to fail.');
        } catch (RuntimeException) {
            // The queue will retry server and network failures.
        }
        $job->handle();

        $delivery->refresh();
        $this->assertSame($eventId, $delivery->event_id);
        $this->assertSame(2, $delivery->attempts);
        $this->assertSame('sent', $delivery->status);

        Http::assertSent(function (Request $request) use ($eventId): bool {
            $timestamp = $request->header('X-Webhook-Timestamp')[0] ?? '';
            $signature = $request->header('X-Webhook-Signature')[0] ?? '';
            $expected = 'sha256='.hash_hmac('sha256', $timestamp.'.'.$request->body(), 'webhook-secret');
            $body = json_decode($request->body(), true, 512, JSON_THROW_ON_ERROR);

            return $body['event_id'] === $eventId
                && $request->header('X-Online-System')[0] === 'ps-web'
                && hash_equals($expected, $signature);
        });
    }

    public function test_exhausted_provisioning_job_records_failure_with_original_references(): void
    {
        Queue::fake();
        $requestId = (string) Str::ulid();
        TenantProvisioningRequest::query()->create([
            'id' => $requestId,
            'external_subscription_id' => 'online-instance:481',
            'domain' => 'failed.ps-web.example',
            'user_id' => 105,
            'name' => 'Client Name',
            'email' => 'client@example.com',
            'payload' => $this->provisioningPayload(),
            'status' => 'provisioning',
        ]);

        $job = new ProvisionTenantJob([
            ...$this->provisioningPayload(),
            'domain' => 'failed.ps-web.example',
            'provisioning_request_id' => $requestId,
        ]);
        $job->failed(new RuntimeException('Migration failed'));

        $this->assertDatabaseHas('tenant_provisioning_requests', [
            'id' => $requestId,
            'status' => 'failed',
            'error_code' => 'PROVISIONING_FAILED',
        ]);
        $delivery = TenantWebhookDelivery::query()->where('event_type', 'tenant.provisioning_failed')->firstOrFail();
        $this->assertSame('online-instance:481', $delivery->external_subscription_id);
        $this->assertSame($requestId, $delivery->payload['data']['provisioning_request_id']);
        $this->assertNull($delivery->payload['data']['tenant_id']);
    }

    /** @return array<string, mixed> */
    private function provisioningPayload(): array
    {
        return [
            'domain' => 'client-name.ps-web.example',
            'name' => 'Client Name',
            'email' => 'client@example.com',
            'started_at' => '2026-09-27T10:00:00Z',
            'ended_at' => '2026-10-27T10:00:00Z',
            'user_id' => 105,
            'external_subscription_id' => 'online-instance:481',
            'auto_renew' => true,
            'branches_limit' => 2,
            'has_ai_subscription' => true,
            'ai_requests_limit' => 20,
        ];
    }
}
