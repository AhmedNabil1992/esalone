<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tenant\PushSubscription;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

class TenantWebPushIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $databaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');

        foreach ($this->databaseFiles as $databaseFile) {
            if (is_file($databaseFile)) {
                unlink($databaseFile);
            }
        }

        parent::tearDown();
    }

    public function test_push_subscriptions_with_the_same_user_id_are_isolated_between_tenants(): void
    {
        $firstTenant = $this->createTenantDatabase();
        $secondTenant = $this->createTenantDatabase();

        tenancy()->initialize($firstTenant);
        $firstUser = $this->createTenantUser('first@example.test');
        $firstUser->updatePushSubscription('https://push.example/first', 'first-key', 'first-token', 'aes128gcm');

        $this->assertSame('tenant', (new PushSubscription)->getConnectionName());
        $this->assertSame(['https://push.example/first'], $firstUser->pushSubscriptions()->pluck('endpoint')->all());

        tenancy()->initialize($secondTenant);
        $secondUser = $this->createTenantUser('second@example.test');
        $secondUser->updatePushSubscription('https://push.example/second', 'second-key', 'second-token', 'aes128gcm');

        $this->assertSame(1, $secondUser->pushSubscriptions()->count());
        $this->assertSame(['https://push.example/second'], $secondUser->pushSubscriptions()->pluck('endpoint')->all());

        tenancy()->initialize($firstTenant);
        $this->assertSame(1, User::query()->findOrFail(1)->pushSubscriptions()->count());
        $this->assertSame(['https://push.example/first'], User::query()->findOrFail(1)->pushSubscriptions()->pluck('endpoint')->all());
    }

    private function createTenantDatabase(): Tenant
    {
        $databaseName = 'webpush-test-'.Str::uuid().'.sqlite';
        $databaseFile = database_path($databaseName);
        $this->databaseFiles[] = $databaseFile;
        touch($databaseFile);

        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
        ]));
        $tenant->setInternal('db_name', $databaseName);
        $tenant->save();

        tenancy()->initialize($tenant);
        (require database_path('migrations/tenant/0001_01_01_000000_create_users_table.php'))->up();
        (require database_path('migrations/tenant/2026_06_24_081449_create_push_subscriptions_table.php'))->up();
        tenancy()->end();

        return $tenant;
    }

    private function createTenantUser(string $email): User
    {
        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Tenant Owner',
            'email' => $email,
            'password' => 'not-used',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail(1);
    }
}
