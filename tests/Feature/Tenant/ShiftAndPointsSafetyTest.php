<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Actions\Tenant\OpenShiftAction;
use App\Actions\Tenant\RedeemCustomerPointsAction;
use App\Filament\Customer\Pages\PointsRedemption;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use App\Tenancy\Bootstrappers\PermissionCacheBootstrapper;
use Database\Factories\UserFactory;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ShiftAndPointsSafetyTest extends TestCase
{
    use RefreshDatabase;

    private string $databaseFile;

    private Tenant $tenant;

    private User $actor;

    private Branch $firstBranch;

    private Branch $secondBranch;

    protected function setUp(): void
    {
        parent::setUp();
        BranchScope::resetCache();
        config()->set('tenancy.database.central_connection', 'sqlite');
        config()->set('tenancy.database.template_tenant_connection', 'sqlite');
        config()->set('tenancy.bootstrappers', [DatabaseTenancyBootstrapper::class, PermissionCacheBootstrapper::class]);
        $name = 'safety-test-'.Str::uuid().'.sqlite';
        $this->databaseFile = database_path($name);
        touch($this->databaseFile);
        $this->tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create(['id' => (string) Str::ulid(), 'is_active' => true]));
        $this->tenant->setInternal('db_name', $name);
        $this->tenant->save();
        $this->tenant->domains()->create(['domain' => 'safety.example.test']);
        tenancy()->initialize($this->tenant);
        (require database_path('migrations/tenant/2026_06_10_082901_create_permission_tables.php'))->up();
        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('branch_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            $table->integer('shift_no');
            $table->unsignedBigInteger('opened_by_user_id');
            $table->date('shift_date');
            $table->dateTime('shift_from');
            $table->decimal('start_amount', 12, 2)->default(0);
            $table->decimal('actual_amount', 12, 2)->default(0);
            $table->string('status');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['branch_id', 'shift_no']);
        });
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->decimal('points', 12, 2);
            $table->decimal('balance', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key');
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('customer_credits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('branch_id');
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('description');
            $table->timestamps();
        });
        $this->firstBranch = Branch::create(['name' => 'First', 'code' => 'FIRST']);
        $this->secondBranch = Branch::create(['name' => 'Second', 'code' => 'SECOND']);
        $this->actor = User::create(Arr::only(UserFactory::new()->raw(), ['name', 'email', 'password']));
        Permission::findOrCreate('Create:Shift', 'web');
        $this->actor->givePermissionTo('Create:Shift');
        $this->actor->branches()->attach([$this->firstBranch->id, $this->secondBranch->id]);
        $this->actingAs($this->actor);
        session(['active_branch_id' => $this->firstBranch->id]);
    }

    protected function tearDown(): void
    {
        tenancy()->end();
        DB::purge('tenant');
        BranchScope::resetCache();
        if (isset($this->databaseFile) && is_file($this->databaseFile)) {
            unlink($this->databaseFile);
        }
        parent::tearDown();
    }

    public function test_each_branch_has_its_own_sequence_even_when_another_branch_is_selected(): void
    {
        $action = app(OpenShiftAction::class);
        $first = $action->execute($this->actor, ['branch_id' => $this->firstBranch->id]);
        $second = $action->execute($this->actor, ['branch_id' => $this->secondBranch->id]);
        $this->assertSame(1, $first->shift_no);
        $this->assertSame(1, $second->shift_no);
        $second->update(['status' => 'close', 'is_active' => false, 'actual_amount' => 75]);
        $next = $action->execute($this->actor, ['branch_id' => $this->secondBranch->id, 'shift_no' => 999]);
        $this->assertSame(2, $next->shift_no);
        $this->assertSame('75.00', $next->start_amount);
        $this->assertSame(3, Shift::withoutGlobalScopes()->count());
        $this->assertSame(3, Shift::nextShiftNumber($this->secondBranch->id));
    }

    public function test_any_open_shift_in_the_branch_prevents_another_even_if_inactive(): void
    {
        $action = app(OpenShiftAction::class);
        $shift = $action->execute($this->actor);
        $shift->update(['is_active' => false]);
        $this->expectException(ValidationException::class);
        $action->execute($this->actor);
    }

    public function test_cannot_open_a_shift_without_permission(): void
    {
        $this->actor->revokePermissionTo('Create:Shift');
        $this->expectException(AuthorizationException::class);
        app(OpenShiftAction::class)->execute($this->actor);
    }

    public function test_cannot_open_a_shift_in_an_unassigned_branch(): void
    {
        $this->actor->branches()->detach($this->secondBranch->id);
        $this->expectException(HttpException::class);
        app(OpenShiftAction::class)->execute($this->actor, ['branch_id' => $this->secondBranch->id]);
    }

    public function test_quick_open_get_cannot_mutate_data_and_post_checks_permissions(): void
    {
        $this->get('http://safety.example.test/tenant/shifts/quick-open')->assertStatus(405);
        $this->assertSame(0, Shift::withoutGlobalScopes()->count());
        $this->actor->revokePermissionTo('Create:Shift');
        $this->post('http://safety.example.test/tenant/shifts/quick-open')->assertForbidden();
        $this->assertSame(0, Shift::withoutGlobalScopes()->count());
    }

    public function test_quick_open_post_succeeds_and_rejects_external_redirects(): void
    {
        $response = $this->post('http://safety.example.test/tenant/shifts/quick-open', ['redirect' => 'https://outside.example/']);
        $response->assertRedirect();
        $this->assertStringNotContainsString('outside.example', $response->headers->get('Location'));
        $this->assertSame(1, Shift::withoutGlobalScopes()->count());
        tenancy()->end();
        $this->assertDatabaseCount('tenants', 1);
        $this->assertFalse(Schema::hasTable('shifts'));
    }

    public function test_points_are_rechecked_against_database_not_a_stale_customer_instance(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'points' => 100, 'balance' => 5]);
        $action = app(RedeemCustomerPointsAction::class);
        $credit = $action->execute((int) $customer->id, 100);
        $this->assertSame('10.00', $credit->amount);
        $this->assertSame('15.00', $customer->fresh()->balance);
        try {
            $action->execute((int) $customer->id, (float) $customer->points);
            $this->fail('The stale balance must be rejected.');
        } catch (ValidationException) {
            $this->assertSame('0.00', $customer->fresh()->points);
            $this->assertSame('15.00', $customer->fresh()->balance);
            $this->assertSame(1, CustomerCredit::withoutGlobalScopes()->count());
        }
    }

    public function test_credit_failure_rolls_back_points_and_balance(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'points' => 100, 'balance' => 5]);
        CustomerCredit::creating(function (): void {
            throw new \RuntimeException('Ledger unavailable');
        });
        try {
            app(RedeemCustomerPointsAction::class)->execute((int) $customer->id, 100);
            $this->fail('Expected ledger failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Ledger unavailable', $exception->getMessage());
            $this->assertSame('100.00', $customer->fresh()->points);
            $this->assertSame('5.00', $customer->fresh()->balance);
        }
    }

    public function test_small_and_excessive_points_requests_are_rejected(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'points' => 100, 'balance' => 0]);
        foreach ([0, -1, 5, 101, INF] as $points) {
            try {
                app(RedeemCustomerPointsAction::class)->execute((int) $customer->id, (float) $points);
                $this->fail('Expected validation failure.');
            } catch (ValidationException) {
                $this->assertSame('100.00', $customer->fresh()->points);
            }
        }
        $this->assertSame(0, CustomerCredit::withoutGlobalScopes()->count());
    }

    public function test_customer_panel_redemption_uses_locked_balance_and_records_credit(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'points' => 100, 'balance' => 5]);
        $this->actingAs($customer, 'customer');
        Filament::setCurrentPanel(Filament::getPanel('customer'));
        Livewire::test(PointsRedemption::class)
            ->callAction('redeem', data: ['points_to_redeem' => 100])
            ->assertHasNoActionErrors();
        $this->assertSame('0.00', $customer->fresh()->points);
        $this->assertSame('15.00', $customer->fresh()->balance);
        $this->assertSame(1, CustomerCredit::withoutGlobalScopes()->count());
    }

    public function test_notification_channels_reject_other_tenants_users_and_legacy_names(): void
    {
        $broadcaster = new NullBroadcaster;
        Broadcast::swap($broadcaster);
        require base_path('routes/channels.php');
        $channels = $broadcaster->getChannels();
        $authorize = $channels['tenant.{tenantId}.users.{id}'];
        $firstChannel = $this->actor->receivesBroadcastNotificationsOn();
        $this->assertTrue($authorize($this->actor, (string) $this->tenant->id, (string) $this->actor->id));
        $this->assertFalse($authorize($this->actor, 'another-tenant', (string) $this->actor->id));
        $this->assertFalse($authorize($this->actor, (string) $this->tenant->id, '999'));
        $this->assertFalse($channels['App.Models.Tenant.User.{id}']($this->actor, $this->actor->id));
        $this->assertFalse($channels['App.Models.User.{id}']($this->actor, $this->actor->id));
        tenancy()->end();
        $this->assertFalse($authorize($this->actor, (string) $this->tenant->id, (string) $this->actor->id));
        $other = Tenant::withoutEvents(fn () => Tenant::create(['id' => (string) Str::ulid()]));
        config()->set('tenancy.bootstrappers', []);
        tenancy()->initialize($other);
        $this->assertNotSame($firstChannel, $this->actor->receivesBroadcastNotificationsOn());
        $this->assertFalse($authorize($this->actor, (string) $this->tenant->id, (string) $this->actor->id));
    }

    public function test_customer_panel_rejects_redemption_after_another_request_spends_the_points(): void
    {
        $customer = Customer::create(['name' => 'Customer', 'points' => 100, 'balance' => 5]);
        $this->actingAs($customer, 'customer');
        Filament::setCurrentPanel(Filament::getPanel('customer'));
        $page = Livewire::test(PointsRedemption::class)
            ->mountAction('redeem');
        app(RedeemCustomerPointsAction::class)->execute((int) $customer->id, 100);
        $page->setActionData(['points_to_redeem' => 100])
            ->callMountedAction()
            ->assertHasActionErrors(['points_to_redeem']);
        $this->assertSame('0.00', $customer->fresh()->points);
        $this->assertSame('15.00', $customer->fresh()->balance);
        $this->assertSame(1, CustomerCredit::withoutGlobalScopes()->count());
    }
}
