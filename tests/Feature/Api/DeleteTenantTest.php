<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Stancl\Tenancy\Events\TenantDeleted;
use Tests\TestCase;

class DeleteTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_deleted_using_provisioning_token(): void
    {
        Event::fake([TenantDeleted::class]);

        config()->set('services.tenant_provisioning.token', 'test-token');

        $tenantId = (string) Str::ulid();
        $domain = $tenantId.'.ps-web.test';

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
            'data' => json_encode([], JSON_THROW_ON_ERROR),
        ]);

        DB::table('domains')->insert([
            'domain' => $domain,
            'tenant_id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withToken('test-token')
            ->deleteJson('/api/tenants/'.$tenantId);

        $response
            ->assertOk()
            ->assertJsonPath('tenant_id', $tenantId);

        $this->assertDatabaseMissing('tenants', [
            'id' => $tenantId,
        ]);
    }

    public function test_delete_tenant_requires_valid_token(): void
    {
        config()->set('services.tenant_provisioning.token', 'test-token');

        $tenantId = (string) Str::ulid();

        DB::table('tenants')->insert([
            'id' => $tenantId,
            'created_at' => now(),
            'updated_at' => now(),
            'data' => json_encode([], JSON_THROW_ON_ERROR),
        ]);

        $response = $this->withToken('wrong-token')
            ->deleteJson('/api/tenants/'.$tenantId);

        $response->assertUnauthorized();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenantId,
        ]);
    }
}
