<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class UpdateTenantEntitlementsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.tenant_provisioning.token', 'entitlements-test-token');
    }

    private function createTenant(array $attributes = []): Tenant
    {
        return Tenant::withoutEvents(fn (): Tenant => Tenant::create(array_merge([
            'id' => (string) Str::ulid(),
            'branches_limit' => 1,
            'has_ai_subscription' => true,
            'ai_requests_limit' => 10,
        ], $attributes)));
    }

    public function test_external_site_can_set_final_entitlements_and_replay_the_same_request(): void
    {
        $tenant = $this->createTenant();
        $tenant->ai_requests_today = 8;
        $tenant->save();
        $otherTenant = $this->createTenant();
        $payload = [
            'branches_limit' => 3,
            'has_ai_subscription' => true,
            'ai_requests_limit' => 20,
        ];

        foreach ([1, 2] as $attempt) {
            $this->withToken('entitlements-test-token')
                ->patchJson('/api/tenants/'.$tenant->id.'/entitlements', $payload)
                ->assertOk()
                ->assertExactJson([
                    'tenant_id' => $tenant->id,
                    'external_subscription_id' => null,
                ] + $payload);
        }

        $tenant->refresh();
        $this->assertSame(3, $tenant->branches_limit);
        $this->assertTrue($tenant->has_ai_subscription);
        $this->assertSame(20, $tenant->ai_requests_limit);
        $this->assertSame(8, $tenant->ai_requests_today);
        $this->assertSame(1, $otherTenant->fresh()->branches_limit);
    }

    public function test_partial_update_preserves_unspecified_entitlements(): void
    {
        $tenant = $this->createTenant();

        $this->withToken('entitlements-test-token')
            ->patchJson('/api/tenants/'.$tenant->id.'/entitlements', ['ai_requests_limit' => 30])
            ->assertOk()
            ->assertJsonPath('branches_limit', 1)
            ->assertJsonPath('has_ai_subscription', true)
            ->assertJsonPath('ai_requests_limit', 30);
    }

    public function test_entitlement_update_requires_the_provisioning_token(): void
    {
        $tenant = $this->createTenant();

        $this->withToken('wrong')
            ->patchJson('/api/tenants/'.$tenant->id.'/entitlements', ['branches_limit' => 2])
            ->assertUnauthorized();

        $this->assertSame(1, $tenant->fresh()->branches_limit);
    }

    public function test_entitlement_update_validates_supported_values_and_requires_one_field(): void
    {
        $tenant = $this->createTenant();

        foreach ([
            [[], ['branches_limit', 'has_ai_subscription', 'ai_requests_limit']],
            [['branches_limit' => 0], ['branches_limit']],
            [['has_ai_subscription' => 'yes'], ['has_ai_subscription']],
            [['ai_requests_limit' => -1], ['ai_requests_limit']],
        ] as [$payload, $invalidFields]) {
            $this->withToken('entitlements-test-token')
                ->patchJson('/api/tenants/'.$tenant->id.'/entitlements', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($invalidFields);
        }
    }

    public function test_missing_and_unready_tenants_cannot_be_updated(): void
    {
        $payload = ['branches_limit' => 2];

        $this->withToken('entitlements-test-token')
            ->patchJson('/api/tenants/missing/entitlements', $payload)
            ->assertNotFound();

        $pendingTenant = $this->createTenant(['provisioning_status' => 'provisioning']);
        $this->withToken('entitlements-test-token')
            ->patchJson('/api/tenants/'.$pendingTenant->id.'/entitlements', $payload)
            ->assertStatus(409);

        $deletingTenant = $this->createTenant();
        $deletingTenant->setAttribute('deletion_started_at', now()->toIso8601String());
        $deletingTenant->save();
        $this->withToken('entitlements-test-token')
            ->patchJson('/api/tenants/'.$deletingTenant->id.'/entitlements', $payload)
            ->assertStatus(410);
    }

    public function test_entitlement_update_cannot_enter_while_another_lifecycle_operation_owns_the_lock(): void
    {
        $tenant = $this->createTenant();
        $lock = Cache::store()->lock('tenant-lifecycle:'.$tenant->id, 600);
        $this->assertTrue($lock->get());

        try {
            $this->withToken('entitlements-test-token')
                ->patchJson('/api/tenants/'.$tenant->id.'/entitlements', ['branches_limit' => 2])
                ->assertStatus(409)
                ->assertJsonPath('message', 'Tenant is busy. Retry the entitlement update shortly.');
            $this->assertSame(1, $tenant->fresh()->branches_limit);
        } finally {
            $lock->release();
        }
    }
}
