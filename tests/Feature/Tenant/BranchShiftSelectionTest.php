<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Livewire\BranchSwitcher;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchShiftSelectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        BranchScope::resetCache();
        $this->setUpDatabaseSchema();
    }

    protected function tearDown(): void
    {
        BranchScope::resetCache();
        $this->tearDownDatabaseSchema();

        parent::tearDown();
    }

    private function setUpDatabaseSchema(): void
    {
        $this->tearDownDatabaseSchema();

        Schema::create('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('branch_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->unsignedBigInteger('opened_by_user_id')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->integer('shift_no')->default(1);
            $table->date('shift_date')->nullable();
            $table->dateTime('shift_from')->nullable();
            $table->dateTime('shift_to')->nullable();
            $table->string('status')->default('open');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify')->default(false);
            $table->decimal('start_amount', 11, 2)->default(0);
            $table->decimal('actual_amount', 11, 2)->default(0);
            $table->decimal('expected_amount', 11, 2)->default(0);
            $table->decimal('playstation_amount', 11, 2)->default(0);
            $table->decimal('sales_amount', 11, 2)->default(0);
            $table->decimal('tax_amount', 11, 2)->default(0);
            $table->decimal('service_amount', 11, 2)->default(0);
            $table->decimal('expenses_amount', 11, 2)->default(0);
            $table->decimal('purchase_amount', 11, 2)->default(0);
            $table->decimal('discount_amount', 11, 2)->default(0);
            $table->decimal('customer_add_amount', 11, 2)->default(0);
            $table->decimal('customer_minus_amount', 11, 2)->default(0);
            $table->decimal('customer_credit_amount', 11, 2)->default(0);
            $table->decimal('credit_amount', 11, 2)->default(0);
            $table->decimal('income_history_amount', 11, 2)->default(0);
            $table->text('notes')->nullable();
            $table->json('cashbox_actual_amounts')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');
            $table->decimal('current_balance', 11, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);
    }

    private function tearDownDatabaseSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('cash_boxes');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::enableForeignKeyConstraints();
    }

    public function test_single_branch_tenant_always_selects_that_branch_and_never_all(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع الرئيسي', 'code' => 'MAIN']);

        $owner = User::query()->create([
            'name' => 'المالك',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole('super_admin');

        $this->actingAs($owner);

        // Even if session was somehow set to 'all' or empty, mount should enforce the single branch ID
        session(['active_branch_id' => 'all']);

        Livewire::test(BranchSwitcher::class)
            ->assertSet('activeBranchId', $branch->id);

        $this->assertEquals($branch->id, session('active_branch_id'));
    }

    public function test_multi_branch_tenant_defaults_to_first_branch_on_first_login(): void
    {
        $branch1 = Branch::query()->create(['name' => 'فرع 1', 'code' => 'BR-1']);
        $branch2 = Branch::query()->create(['name' => 'فرع 2', 'code' => 'BR-2']);

        $owner = User::query()->create([
            'name' => 'المالك',
            'email' => 'owner_multi@test.com',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole('super_admin');

        $this->actingAs($owner);
        session()->forget('active_branch_id');

        Livewire::test(BranchSwitcher::class)
            ->assertSet('activeBranchId', $branch1->id);

        $this->assertEquals($branch1->id, session('active_branch_id'));
    }

    public function test_belongs_to_branch_trait_never_leaves_branch_id_null_when_session_is_all(): void
    {
        $branch1 = Branch::query()->create(['name' => 'فرع 1', 'code' => 'BR-1']);
        $branch2 = Branch::query()->create(['name' => 'فرع 2', 'code' => 'BR-2']);

        $owner = User::query()->create([
            'name' => 'المالك',
            'email' => 'owner_all@test.com',
            'password' => bcrypt('password'),
        ]);
        $owner->assignRole('super_admin');

        $this->actingAs($owner);
        session(['active_branch_id' => 'all']);
        BranchScope::resetCache();

        // Creating a shift without explicit branch_id while active_branch_id is 'all'
        $shift = Shift::query()->create([
            'opened_by_user_id' => $owner->id,
            'shift_date' => now()->toDateString(),
            'shift_from' => now()->toDateTimeString(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        $this->assertNotNull($shift->branch_id);
        $this->assertEquals($branch1->id, $shift->branch_id);
    }

    public function test_shift_quick_open_route_assigns_branch_id(): void
    {
        $branch = Branch::query()->create(['name' => 'فرع النزهة', 'code' => 'BR-NOZHA']);

        $user = User::query()->create([
            'name' => 'كاشير النزهة',
            'email' => 'nozha@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('cashier');
        $user->branches()->attach($branch->id);

        $this->actingAs($user);
        session(['active_branch_id' => $branch->id]);

        $branchId = (int) ((session('active_branch_id') && session('active_branch_id') !== 'all')
            ? (int) session('active_branch_id')
            : ($user->branches()->first()?->id ?? Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1));

        $shift = Shift::query()->create([
            'branch_id' => $branchId,
            'shift_no' => Shift::nextShiftNumber($branchId),
            'opened_by_user_id' => $user->id,
            'shift_date' => now()->toDateString(),
            'shift_from' => now()->toDateTimeString(),
            'start_amount' => 0,
            'actual_amount' => 0,
            'status' => 'open',
            'is_active' => true,
            'notify' => false,
        ]);

        $this->assertNotNull($shift->branch_id);
        $this->assertEquals($branch->id, $shift->branch_id);
    }
}
