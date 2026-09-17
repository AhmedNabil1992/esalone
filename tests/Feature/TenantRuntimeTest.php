<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Tenant\Reservations\ReservationsResource;
use App\Jobs\GenerateTenantPermissionsJob;
use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Tenancy\Bootstrappers\PermissionCacheBootstrapper;
use App\Tenancy\Bootstrappers\TimezoneBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

class TenantRuntimeTest extends TestCase
{
    use RefreshDatabase;

    private array $databaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class, PermissionCacheBootstrapper::class]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');
        foreach ($this->databaseFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        parent::tearDown();
    }

    private function createTenantDatabase(): Tenant
    {
        $name = 'runtime-test-'.Str::uuid().'.sqlite';
        $this->databaseFiles[] = database_path($name);
        touch(database_path($name));
        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(), 'is_active' => true,
        ]));
        $tenant->setInternal('db_name', $name);
        $tenant->save();
        tenancy()->initialize($tenant);
        (require database_path('migrations/tenant/2026_06_10_082901_create_permission_tables.php'))->up();
        tenancy()->end();

        return $tenant;
    }

    public function test_permission_cache_and_database_are_isolated_between_tenants_and_central(): void
    {
        $first = $this->createTenantDatabase();
        $second = $this->createTenantDatabase();
        $registrar = app(PermissionRegistrar::class);
        $centralKey = $registrar->cacheKey;
        Permission::create(['name' => 'central-only', 'guard_name' => 'web']);
        $this->assertSame(['central-only'], $registrar->getPermissions()->pluck('name')->all());

        tenancy()->initialize($first);
        Permission::create(['name' => 'first-only', 'guard_name' => 'web']);
        $firstKey = $registrar->cacheKey;
        $this->assertSame(['first-only'], $registrar->getPermissions()->pluck('name')->all());

        tenancy()->initialize($second);
        Permission::create(['name' => 'second-only', 'guard_name' => 'web']);
        $this->assertNotSame($firstKey, $registrar->cacheKey);
        $this->assertSame(['second-only'], $registrar->getPermissions()->pluck('name')->all());

        tenancy()->initialize($first);
        $this->assertSame(['first-only'], $registrar->getPermissions()->pluck('name')->all());
        tenancy()->end();
        $this->assertSame($centralKey, $registrar->cacheKey);
        $this->assertSame(['central-only'], $registrar->getPermissions()->pluck('name')->all());
    }

    public function test_generation_assigns_web_permissions_only_to_the_target_tenant(): void
    {
        $tenant = $this->createTenantDatabase();
        Artisan::shouldReceive('call')->once()->with('shield:generate', \Mockery::type('array'))->andReturnUsing(function (): int {
            Permission::create(['name' => 'View:Invoice', 'guard_name' => 'web']);
            Permission::create(['name' => 'View:Customer', 'guard_name' => 'customer']);

            return 0;
        });
        (new GenerateTenantPermissionsJob((string) $tenant->id))->handle();
        $this->assertFalse(tenancy()->initialized);
        $this->assertSame(0, Permission::count());
        tenancy()->initialize($tenant);
        $this->assertSame(['View:Invoice'], Role::findByName('super_admin', 'web')->permissions->pluck('name')->all());
    }

    public function test_failed_shield_exit_is_not_reported_as_success_and_restores_central_context(): void
    {
        $tenant = $this->createTenantDatabase();
        Artisan::shouldReceive('call')->once()->andReturn(1);
        Artisan::shouldReceive('output')->once()->andReturn('Generation prohibited');
        try {
            (new GenerateTenantPermissionsJob((string) $tenant->id))->handle();
            $this->fail('Expected generation failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Generation prohibited', $exception->getMessage());
            $this->assertFalse(tenancy()->initialized);
        }
    }

    public function test_empty_permission_generation_fails(): void
    {
        $tenant = $this->createTenantDatabase();
        Artisan::shouldReceive('call')->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('No entities');
        $this->expectExceptionMessage('Shield generated no web permissions');
        (new GenerateTenantPermissionsJob((string) $tenant->id))->handle();
    }

    public function test_real_shield_generation_is_repeatable(): void
    {
        $tenant = $this->createTenantDatabase();
        $job = new GenerateTenantPermissionsJob((string) $tenant->id);
        $job->handle();
        tenancy()->initialize($tenant);
        $count = Permission::where('guard_name', 'web')->count();
        $this->assertGreaterThan(0, $count);
        tenancy()->end();
        $job->handle();
        tenancy()->initialize($tenant);
        $this->assertSame($count, Permission::where('guard_name', 'web')->count());
        $this->assertSame($count, Role::findByName('super_admin', 'web')->permissions()->count());
    }

    public function test_alert_commands_skip_central_and_unready_tenants_without_querying_operational_tables(): void
    {
        $this->artisan('tenants:send-booking-alerts')->assertSuccessful();
        $this->artisan('tenants:send-session-expiry-alerts')->assertSuccessful();
        $tenant = $this->createTenantDatabase();
        $tenant->update(['provisioning_status' => 'provisioning']);
        tenancy()->initialize($tenant);
        $this->artisan('tenants:send-booking-alerts')->assertSuccessful();
        $this->artisan('tenants:send-session-expiry-alerts')->assertSuccessful();
        $tenant->update(['provisioning_status' => 'ready', 'ended_at' => now()->subDay()]);
        $this->artisan('tenants:send-booking-alerts')->assertSuccessful();
        $this->artisan('tenants:send-session-expiry-alerts')->assertSuccessful();
    }

    public function test_booking_notification_url_resolves_without_central_hostname(): void
    {
        $url = ReservationsResource::getUrl('index', panel: 'admin', isAbsolute: false);
        $this->assertStringStartsWith('/admin/', $url);
        $this->assertStringNotContainsString('://', $url);
    }

    public function test_tenant_context_does_not_mutate_the_process_timezone(): void
    {
        date_default_timezone_set('UTC');
        $bootstrapper = new TimezoneBootstrapper;
        $bootstrapper->bootstrap(new Tenant);
        $this->assertSame('UTC', date_default_timezone_get());
        $bootstrapper->revert();
        $this->assertSame('UTC', date_default_timezone_get());
    }

    public function test_database_jobs_stay_in_the_central_database_inside_tenant_context(): void
    {
        $tenant = $this->createTenantDatabase();
        tenancy()->initialize($tenant);
        Queue::connection('database')->push(new GenerateTenantPermissionsJob((string) $tenant->id));
        $this->assertFalse(DB::connection('tenant')->getSchemaBuilder()->hasTable('jobs'));
        tenancy()->end();
        $this->assertSame(1, DB::connection('sqlite')->table('jobs')->count());
        $this->assertGreaterThan((new ProvisionTenantJob([]))->timeout, config('queue.connections.database.retry_after'));
    }
}
