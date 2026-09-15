<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Http\Controllers\Tenant\ImpersonateUserController;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Tenancy\Bootstrappers\PermissionCacheBootstrapper;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Tests\TestCase;

class TenantImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private string $databaseFile;

    private Tenant $tenant;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.tenant_provisioning.token', 'impersonation-api-token');
        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class, PermissionCacheBootstrapper::class]);
        config()->set('auth.providers.users.model', User::class);

        $databaseName = 'impersonation-test-'.Str::uuid().'.sqlite';
        $this->databaseFile = database_path($databaseName);
        touch($this->databaseFile);

        $this->tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
            'provisioning_status' => 'ready',
            'ended_at' => now()->addMonth(),
        ]));
        $this->tenant->setInternal('db_name', $databaseName);
        $this->tenant->save();
        $this->tenant->domains()->create(['domain' => 'support.example.test']);

        tenancy()->initialize($this->tenant);
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        User::create(['name' => 'Legacy Support', 'email' => 'admin@etech-valley.com', 'password' => 'unknown-password']);
        $this->owner = User::create(['name' => 'Owner', 'email' => 'owner@example.test', 'password' => 'owner-password']);
        tenancy()->end();
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');
        if (isset($this->databaseFile) && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }

        parent::tearDown();
    }

    public function test_api_creates_a_single_use_owner_impersonation_url(): void
    {
        $response = $this->withToken('impersonation-api-token')
            ->postJson('/api/tenants/'.$this->tenant->id.'/impersonation')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('tenant_id', $this->tenant->id)
            ->assertJsonPath('expires_in', 60);

        $url = (string) $response->json('impersonation_url');
        $this->assertSame(128, strlen((string) $response->json('token')));
        $this->assertSame('support.example.test', parse_url($url, PHP_URL_HOST));
        $token = basename((string) parse_url($url, PHP_URL_PATH));
        $this->assertSame((string) $this->owner->id, ImpersonationToken::findOrFail($token)->user_id);

        $this->get($url)->assertRedirect('/admin');
        $this->assertAuthenticatedAs($this->owner, 'web');
        $this->assertNull(ImpersonationToken::find($token));
    }

    public function test_api_rejects_requests_without_the_shared_api_token(): void
    {
        $this->postJson('/api/tenants/'.$this->tenant->id.'/impersonation')->assertUnauthorized();
        $this->assertDatabaseCount('tenant_user_impersonation_tokens', 0);
    }

    public function test_impersonation_token_cannot_be_used_on_another_tenant_domain(): void
    {
        $url = (string) $this->withToken('impersonation-api-token')
            ->postJson('/api/tenants/'.$this->tenant->id.'/impersonation')
            ->json('impersonation_url');
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $this->get('http://another.example.test/impersonate/'.$token)->assertNotFound();
        $this->assertNotNull(ImpersonationToken::find($token));
    }

    public function test_tenant_timezone_does_not_make_a_fresh_token_look_expired(): void
    {
        $url = (string) $this->withToken('impersonation-api-token')
            ->postJson('/api/tenants/'.$this->tenant->id.'/impersonation')
            ->json('impersonation_url');
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        tenancy()->initialize($this->tenant);
        date_default_timezone_set('Africa/Cairo');

        $response = app(ImpersonateUserController::class)($token);

        $this->assertStringEndsWith('/admin', $response->getTargetUrl());
        $this->assertAuthenticatedAs($this->owner, 'web');
        $this->assertNull(ImpersonationToken::find($token));
        $this->assertSame('Africa/Cairo', date_default_timezone_get());
    }

    public function test_legacy_support_credentials_sessions_and_permissions_are_disabled(): void
    {
        tenancy()->initialize($this->tenant);
        $supportUser = User::where('email', 'admin@etech-valley.com')->firstOrFail();
        DB::table('sessions')->insert(['id' => 'legacy-session', 'user_id' => $supportUser->id]);
        DB::table('password_reset_tokens')->insert(['email' => $supportUser->email, 'token' => 'reset-token']);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_type' => User::class, 'model_id' => $supportUser->id]);
        DB::table('model_has_permissions')->insert(['permission_id' => 1, 'model_type' => User::class, 'model_id' => $supportUser->id]);

        (require database_path('migrations/tenant/2026_09_10_045534_disable_legacy_support_user.php'))->up();

        $disabledUser = User::findOrFail($supportUser->id);
        $this->assertSame('disabled-support-'.$supportUser->id.'@invalid.local', $disabledUser->email);
        $this->assertFalse(Hash::check('unknown-password', $disabledUser->password));
        $this->assertSame(0, DB::table('sessions')->where('user_id', $supportUser->id)->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', 'admin@etech-valley.com')->count());
        $this->assertSame(0, DB::table('model_has_roles')->where('model_id', $supportUser->id)->count());
        $this->assertSame(0, DB::table('model_has_permissions')->where('model_id', $supportUser->id)->count());
    }
}
