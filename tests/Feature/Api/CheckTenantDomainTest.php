<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckTenantDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_available_when_domain_does_not_exist(): void
    {
        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $domain = 'new-gaming-cafe.ps-web.test';

        $response = $this->withToken('valid-secret-token')
            ->getJson('/api/tenants/check-domain?domain='.$domain);

        $response->assertOk()
            ->assertExactJson([
                'domain' => $domain,
                'exists' => false,
                'available' => true,
                'message' => 'الدومين متاح للاستخدام',
            ]);
    }

    public function test_it_returns_exists_when_domain_already_registered(): void
    {
        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $tenantId = (string) Str::ulid();
        $domain = 'existing-cafe.ps-web.test';

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

        $response = $this->withToken('valid-secret-token')
            ->postJson('/api/tenants/check-domain', [
                'domain' => $domain,
            ]);

        $response->assertOk()
            ->assertExactJson([
                'domain' => $domain,
                'exists' => true,
                'available' => false,
                'message' => 'الدومين مستخدم بالفعل',
            ]);
    }

    public function test_it_validates_required_domain(): void
    {
        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $response = $this->withToken('valid-secret-token')
            ->getJson('/api/tenants/check-domain');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['domain']);
    }

    public function test_it_requires_valid_token(): void
    {
        config()->set('services.tenant_provisioning.token', 'valid-secret-token');

        $response = $this->withToken('wrong-token')
            ->getJson('/api/tenants/check-domain?domain=test.ps-web.test');

        $response->assertUnauthorized();
    }
}
