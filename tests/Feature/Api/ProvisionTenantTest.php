<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\ProvisionTenantJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProvisionTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_provisioned_using_valid_token(): void
    {
        Queue::fake();

        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $payload = [
            'domain' => 'my-subdomain.ps-web.test',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'started_at' => '2026-08-31',
            'ended_at' => '2027-08-31',
            'user_id' => 123,
            'auto_renew' => true,
            'branches_limit' => 2,
        ];

        $response = $this->withToken('valid-secret-token')
            ->postJson('/api/tenants', $payload);

        $response->assertStatus(202);
        $response->assertJsonStructure(['message']);

        Queue::assertPushed(ProvisionTenantJob::class, function (ProvisionTenantJob $job) use ($payload) {
            return $job->data['domain'] === $payload['domain']
                && $job->data['name'] === $payload['name']
                && $job->data['email'] === $payload['email']
                && $job->data['user_id'] === $payload['user_id'];
        });
    }

    public function test_provision_tenant_rejects_unauthorized_token(): void
    {
        Queue::fake();

        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $response = $this->withToken('wrong-token')
            ->postJson('/api/tenants', [
                'domain' => 'subdomain.ps-web.test',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'started_at' => '2026-08-31',
                'ended_at' => '2027-08-31',
                'user_id' => 123,
                'auto_renew' => true,
            ]);

        $response->assertUnauthorized();
        Queue::assertNothingPushed();
    }

    public function test_provision_tenant_validates_required_fields(): void
    {
        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $response = $this->withToken('valid-secret-token')
            ->postJson('/api/tenants', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'domain',
            'name',
            'email',
            'started_at',
            'ended_at',
            'user_id',
            'auto_renew',
        ]);
    }
}
