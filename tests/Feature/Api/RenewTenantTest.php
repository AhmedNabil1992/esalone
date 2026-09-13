<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\DeleteExpiredTenantDataJob;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Stancl\Tenancy\Events\DeletingTenant;
use Tests\TestCase;

class RenewTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.tenant_provisioning.token', 'renewal-test-token');
        config()->set('tenancy.expiration_grace_days', 3);
        $this->travelTo(now()->startOfSecond());
    }

    private function createTenant(array $attributes = []): Tenant
    {
        return Tenant::withoutEvents(fn (): Tenant => Tenant::create(array_merge([
            'id' => (string) Str::ulid(), 'is_active' => true, 'ended_at' => now()->subDay(),
        ], $attributes)));
    }

    public function test_external_site_can_renew_within_grace_and_replay_the_same_request(): void
    {
        $tenant = $this->createTenant(['is_active' => false]);
        $other = $this->createTenant();
        $endedAt = now()->addMonth()->toIso8601String();
        foreach ([1, 2] as $attempt) {
            $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$tenant->id.'/renew', ['ended_at' => $endedAt])
                ->assertOk()->assertJsonPath('tenant_id', $tenant->id)->assertJsonPath('is_active', true);
        }
        $this->assertTrue($tenant->fresh()->ended_at->equalTo($endedAt));
        $this->assertTrue($other->fresh()->ended_at->isPast());
        Event::fake([DeletingTenant::class]);
        (new DeleteExpiredTenantDataJob)->handle();
        $this->assertNotNull($tenant->fresh());
    }

    public function test_renewal_requires_the_provisioning_token(): void
    {
        $tenant = $this->createTenant();
        $this->withToken('wrong')->postJson('/api/tenants/'.$tenant->id.'/renew', ['ended_at' => now()->addMonth()->toIso8601String()])->assertUnauthorized();
        $this->assertTrue($tenant->fresh()->ended_at->isPast());
    }

    public function test_renewal_is_refused_at_and_after_the_three_day_boundary(): void
    {
        foreach ([now()->subDays(3), now()->subDays(4)] as $expiry) {
            $tenant = $this->createTenant(['ended_at' => $expiry]);
            $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$tenant->id.'/renew', ['ended_at' => now()->addMonth()->toIso8601String()])->assertStatus(410);
            $this->assertTrue($tenant->fresh()->ended_at->equalTo($expiry));
        }
    }

    public function test_renewal_rejects_shortening_and_invalid_dates(): void
    {
        $tenant = $this->createTenant(['ended_at' => now()->addMonth()]);
        foreach (['not-a-date', now()->subDay()->toIso8601String(), now()->addDay()->toIso8601String()] as $date) {
            $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$tenant->id.'/renew', ['ended_at' => $date])
                ->assertUnprocessable()->assertJsonValidationErrors('ended_at');
        }
    }

    public function test_missing_and_unready_tenants_cannot_be_renewed(): void
    {
        $payload = ['ended_at' => now()->addMonth()->toIso8601String()];
        $this->withToken('renewal-test-token')->postJson('/api/tenants/missing/renew', $payload)->assertNotFound();
        $pending = $this->createTenant(['provisioning_status' => 'provisioning']);
        $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$pending->id.'/renew', $payload)->assertStatus(409);
        $deleting = $this->createTenant();
        $deleting->setAttribute('deletion_started_at', now()->toIso8601String());
        $deleting->save();
        $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$deleting->id.'/renew', $payload)->assertStatus(410);
    }

    public function test_renewal_cannot_enter_while_deletion_owns_the_lifecycle_lock(): void
    {
        $this->travelBack();
        $tenant = $this->createTenant();
        $lock = Cache::store()->lock('tenant-lifecycle:'.$tenant->id, 600);
        $this->assertTrue($lock->get());
        try {
            $this->withToken('renewal-test-token')->postJson('/api/tenants/'.$tenant->id.'/renew', ['ended_at' => now()->addMonth()->toIso8601String()])->assertStatus(409);
            $this->assertTrue($tenant->fresh()->ended_at->isPast());
        } finally {
            $lock->release();
        }
    }

    public function test_cleanup_cannot_delete_a_tenant_while_renewal_owns_the_lock(): void
    {
        $this->travelBack();
        $tenant = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $lock = Cache::store()->lock('tenant-lifecycle:'.$tenant->id, 600);
        $this->assertTrue($lock->get());
        try {
            (new DeleteExpiredTenantDataJob)->handle();
            $this->fail('Expected cleanup to report the unavailable lock.');
        } catch (\RuntimeException) {
            $this->assertNotNull($tenant->fresh());
        } finally {
            $lock->release();
        }
    }
}
