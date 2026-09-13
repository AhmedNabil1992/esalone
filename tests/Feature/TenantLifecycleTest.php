<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\DeleteExpiredTenantDataJob;
use App\Jobs\ProvisionTenantJob;
use App\Mail\TenantProvisionedMail;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Tenancy\Bootstrappers\PermissionCacheBootstrapper;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Events\DeletingTenant;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager;
use Tests\TestCase;

class TenantLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private array $databaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.expiration_grace_days', 3);
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

    private function createTenant(array $attributes = []): Tenant
    {
        return Tenant::withoutEvents(fn (): Tenant => Tenant::create(array_merge([
            'id' => (string) Str::ulid(), 'is_active' => true,
        ], $attributes)));
    }

    public function test_cleanup_preserves_active_no_expiry_grace_period_and_provisioning_tenants(): void
    {
        Event::fake([DeletingTenant::class]);
        $expired = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $active = $this->createTenant(['ended_at' => now()->addDay()]);
        $grace = $this->createTenant(['ended_at' => now()->subDays(2)]);
        $permanent = $this->createTenant();
        $pending = $this->createTenant(['ended_at' => now()->subDays(4), 'provisioning_status' => 'provisioning']);
        (new DeleteExpiredTenantDataJob)->handle();
        $this->assertNull($expired->fresh());
        foreach ([$active, $grace, $permanent, $pending] as $tenant) {
            $this->assertNotNull($tenant->fresh());
        }
    }

    public function test_dry_run_does_not_dispatch_deletion(): void
    {
        Queue::fake();
        $tenant = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $this->artisan('tenants:cleanup-expired --dry-run')->assertSuccessful();
        $this->assertNotNull($tenant->fresh());
        Queue::assertNothingPushed();
    }

    public function test_database_deletion_failure_keeps_tenant_metadata_for_retry(): void
    {
        $tenant = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $manager = Mockery::mock(SQLiteDatabaseManager::class);
        $manager->shouldReceive('setConnection');
        $manager->shouldReceive('databaseExists')->andReturn(true);
        $manager->shouldReceive('deleteDatabase')->once()->andThrow(new RuntimeException('Database unavailable'));
        $this->app->instance(SQLiteDatabaseManager::class, $manager);
        try {
            (new DeleteExpiredTenantDataJob)->handle();
            $this->fail('Expected cleanup failure.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('could not be deleted', $exception->getMessage());
            $this->assertNotNull($tenant->fresh());
            $this->assertFalse(tenancy()->initialized);
        }
    }

    public function test_cleanup_removes_only_the_expired_tenant_database_and_domains(): void
    {
        $expired = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $active = $this->createTenant(['ended_at' => now()->addDay()]);
        foreach ([$expired, $active] as $tenant) {
            $name = 'lifecycle-test-'.Str::uuid().'.sqlite';
            $this->databaseFiles[] = database_path($name);
            touch(database_path($name));
            $tenant->setInternal('db_name', $name);
            $tenant->save();
            $tenant->domains()->create(['domain' => $tenant->id.'.example.test']);
        }
        (new DeleteExpiredTenantDataJob)->handle();
        $this->assertFileDoesNotExist(database_path($expired->database()->getName()));
        $this->assertFileExists(database_path($active->database()->getName()));
        $this->assertDatabaseMissing('domains', ['tenant_id' => $expired->id]);
        $this->assertDatabaseHas('domains', ['tenant_id' => $active->id]);
        $this->assertNotNull($active->fresh());
    }

    public function test_queued_cleanup_rechecks_current_expiry_after_renewal(): void
    {
        $tenant = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $job = new DeleteExpiredTenantDataJob;
        $tenant->update(['ended_at' => now()->addMonth()]);
        $job->handle();
        $this->assertNotNull($tenant->fresh());
    }

    public function test_cleanup_removes_expired_tenant_files_and_cache_without_touching_other_files(): void
    {
        $expired = $this->createTenant(['ended_at' => now()->subDays(4)]);
        $active = $this->createTenant(['ended_at' => now()->addDay()]);
        $originalStorage = storage_path();
        $testStorage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'tenant-cleanup-'.Str::uuid();
        mkdir($testStorage);
        $this->app->useStoragePath($testStorage);
        $suffix = config('tenancy.filesystem.suffix_base');
        $manager = Mockery::mock(SQLiteDatabaseManager::class);
        $manager->shouldReceive('setConnection');
        $manager->shouldReceive('databaseExists')->andReturn(false);
        $this->app->instance(SQLiteDatabaseManager::class, $manager);
        try {
            foreach ([$expired, $active] as $tenant) {
                mkdir(storage_path($suffix.$tenant->id));
                file_put_contents(storage_path($suffix.$tenant->id.'/upload.txt'), 'test upload');
                Cache::put(config('permission.cache.key').'.tenant.'.$tenant->id, 'permissions');
            }
            file_put_contents(storage_path('shared.txt'), 'shared');
            (new DeleteExpiredTenantDataJob)->handle();
            $this->assertDirectoryDoesNotExist(storage_path($suffix.$expired->id));
            $this->assertFileExists(storage_path($suffix.$active->id.'/upload.txt'));
            $this->assertFileExists(storage_path('shared.txt'));
            $this->assertNull(Cache::get(config('permission.cache.key').'.tenant.'.$expired->id));
            $this->assertSame('permissions', Cache::get(config('permission.cache.key').'.tenant.'.$active->id));
        } finally {
            $this->app->useStoragePath($originalStorage);
            File::deleteDirectory($testStorage);
        }
    }

    public function test_provisioning_completes_permissions_before_queueing_welcome_email(): void
    {
        Mail::fake();
        $this->prepareProvisioningDatabase();
        (new ProvisionTenantJob($this->provisioningData()))->handle();
        $tenant = Tenant::query()->firstOrFail();
        $this->assertSame('ready', $tenant->getAttribute('provisioning_status'));
        $this->assertFalse(tenancy()->initialized);
        Mail::assertQueued(TenantProvisionedMail::class);
        tenancy()->initialize($tenant);
        $this->assertGreaterThan(0, Permission::count());
        $this->assertTrue(User::where('email', 'owner@example.test')->firstOrFail()->hasRole('super_admin'));
        $this->assertFalse(User::where('email', 'admin@etech-valley.com')->exists());
    }

    public function test_mail_queue_failure_does_not_delete_a_provisioned_tenant(): void
    {
        $this->prepareProvisioningDatabase();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('queue')->once()->andThrow(new RuntimeException('Mail queue unavailable'));
        (new ProvisionTenantJob($this->provisioningData()))->handle();
        $this->assertSame('ready', Tenant::query()->firstOrFail()->getAttribute('provisioning_status'));
    }

    public function test_cleanup_is_scheduled_daily_and_uses_the_provisioning_queue(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event): bool => str_contains($event->command ?? '', 'tenants:cleanup-expired'));
        $this->assertNotNull($event);
        $this->assertSame('0 3 * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $job = new DeleteExpiredTenantDataJob;
        $this->assertSame('database', $job->connection);
        $this->assertSame('tenant-provisioning', $job->queue);
    }

    public function test_failed_database_provisioning_does_not_leave_an_orphan_tenant(): void
    {
        Mail::fake();
        Event::forget(TenantCreated::class);
        Event::listen(TenantCreated::class, function (): void {
            throw new RuntimeException('Migration failed');
        });
        try {
            (new ProvisionTenantJob($this->provisioningData()))->handle();
            $this->fail('Expected provisioning failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Migration failed', $exception->getMessage());
            $this->assertSame(0, Tenant::count());
            $this->assertFalse(tenancy()->initialized);
            Mail::assertNothingQueued();
        }
    }

    private function prepareProvisioningDatabase(): void
    {
        Event::forget(TenantCreated::class);
        Event::listen(TenantCreated::class, function (TenantCreated $event): void {
            $tenant = $event->tenant;
            $name = 'provision-test-'.Str::uuid().'.sqlite';
            $this->databaseFiles[] = database_path($name);
            touch(database_path($name));
            $tenant->setInternal('db_name', $name);
            $tenant->save();
            tenancy()->initialize($tenant);
            (require database_path('migrations/tenant/2026_06_10_082901_create_permission_tables.php'))->up();
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
            tenancy()->end();
        });
    }

    private function provisioningData(): array
    {
        return [
            'domain' => 'owner.example.test', 'name' => 'Owner', 'email' => 'owner@example.test',
            'started_at' => now(), 'ended_at' => now()->addMonth(), 'user_id' => 1, 'auto_renew' => false,
        ];
    }
}
