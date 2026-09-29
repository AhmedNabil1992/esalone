<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureTenantIsActive;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantSuspensionAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_suspended_tenant_visitors_see_the_suspension_page(): void
    {
        $tenant = $this->createTenant(false, 'suspended', 'suspended.example.test');

        $this->get('http://suspended.example.test/')
            ->assertStatus(423)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('الموقع متوقف مؤقتًا')
            ->assertSee('suspended.example.test');

        $this->assertFalse((bool) $tenant->fresh()->is_active);
    }

    public function test_suspended_tenant_cannot_open_the_filament_admin_panel(): void
    {
        $this->createTenant(false, 'suspended', 'suspended-admin.example.test');

        $this->get('http://suspended-admin.example.test/admin')
            ->assertStatus(423)
            ->assertSee('الموقع متوقف مؤقتًا');
    }

    public function test_suspended_tenant_json_requests_receive_a_machine_readable_error(): void
    {
        $this->createTenant(false, 'suspended', 'suspended-json.example.test');

        $this->getJson('http://suspended-json.example.test/menu')
            ->assertStatus(423)
            ->assertExactJson([
                'error_code' => 'TENANT_SUSPENDED',
                'message' => 'Tenant is suspended.',
                'errors' => [],
            ]);
    }

    public function test_active_tenant_is_allowed_through_the_middleware(): void
    {
        $tenant = $this->createTenant(true, 'ready');
        tenancy()->tenant = $tenant;

        $response = app(EnsureTenantIsActive::class)->handle(
            Request::create('/admin', 'GET'),
            fn () => response('allowed'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('allowed', $response->getContent());
    }

    public function test_central_site_remains_accessible(): void
    {
        $centralDomain = (string) config('tenancy.central_domains.0');

        $this->get('http://'.$centralDomain.'/')
            ->assertOk();
    }

    private function createTenant(bool $isActive, string $status, ?string $domain = null): Tenant
    {
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => $isActive,
            'provisioning_status' => $status,
        ]));

        if ($domain) {
            $tenant->domains()->create(['domain' => $domain]);
        }

        return $tenant;
    }
}
