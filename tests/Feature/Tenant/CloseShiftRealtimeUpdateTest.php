<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Filament\Resources\Tenant\Shifts\Shifts\Pages\CloseShift;
use App\Http\Controllers\Tenant\PrintShiftCloseController;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Filament\Facades\Filament;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CloseShiftRealtimeUpdateTest extends TestCase
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
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['branch_id', 'user_id']);
        });

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
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

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->string('invoice_no')->nullable();
            $table->string('invoice_type')->default('direct_sale');
            $table->string('status')->default('posted');
            $table->decimal('play_amount', 11, 2)->default(0);
            $table->decimal('order_amount', 11, 2)->default(0);
            $table->decimal('subtotal', 11, 2)->default(0);
            $table->decimal('tax_amount', 11, 2)->default(0);
            $table->decimal('service_amount', 11, 2)->default(0);
            $table->decimal('discount_amount', 11, 2)->default(0);
            $table->decimal('remain_amount', 11, 2)->default(0);
            $table->decimal('final_amount', 11, 2)->default(0);
            $table->decimal('paid_amount', 11, 2)->default(0);
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();
            $table->timestamps();
        });

        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('payment_method')->default('cash');
            $table->decimal('amount', 11, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cash_box_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('cash_box_id')->nullable()->constrained('cash_boxes');
            $table->string('operation')->default('expense');
            $table->string('direction')->default('in');
            $table->decimal('amount', 11, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('expense_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('expense_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('cash_box_id')->nullable()->constrained('cash_boxes');
            $table->foreignId('expense_type_id')->nullable()->constrained('expense_types');
            $table->decimal('amount', 11, 2)->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->foreignId('shift_id')->nullable()->constrained('shifts');
            $table->foreignId('cash_box_id')->nullable()->constrained('cash_boxes');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers');
            $table->decimal('total_amount', 11, 2)->default(0);
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $viewAnyPerm = Permission::create(['name' => 'ViewAny:Shift', 'guard_name' => 'web']);
        $viewPerm = Permission::create(['name' => 'View:Shift', 'guard_name' => 'web']);
        $updatePerm = Permission::create(['name' => 'Update:Shift', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:Shift', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo([$viewAnyPerm, $viewPerm, $updatePerm]);
    }

    private function tearDownDatabaseSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('expense_histories');
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('cash_box_ledger_entries');
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('cash_boxes');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::enableForeignKeyConstraints();
    }

    public function test_page_mounts_and_computes_initial_fingerprint(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 100]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);
        $component->assertSet('hasNewTransactions', false);
        $this->assertNotEmpty($component->get('dataFingerprint'));
    }

    public function test_create_permission_allows_blind_close_without_view_permissions(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'BLIND']);
        $user = User::query()->create([
            'name' => 'Cashier',
            'email' => 'blind-close@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->givePermissionTo('Create:Shift');
        $user->branches()->attach($branch->id);
        $this->actingAs($user);

        CashBox::query()->create([
            'branch_id' => $branch->id,
            'name' => 'الخزنة الرئيسية',
            'current_balance' => 100,
            'is_active' => true,
        ]);
        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        Livewire::test(CloseShift::class, ['record' => $shift->id])
            ->assertOk()
            ->assertSee('تقفيل وتسوية الوردية')
            ->assertDontSee('تفاصيل الإيرادات');
    }

    public function test_user_without_create_or_update_permission_cannot_access_close_shift(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'DENIED']);
        $user = User::query()->create([
            'name' => 'No Shift Permission',
            'email' => 'no-shift-permission@test.com',
            'password' => bcrypt('password'),
        ]);
        $user->branches()->attach($branch->id);
        $this->actingAs($user);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 0,
            'status' => 'open',
            'is_active' => true,
        ]);

        Livewire::test(CloseShift::class, ['record' => $shift->id])
            ->assertForbidden();
    }

    public function test_check_for_updates_detects_new_invoice_and_refreshes_stats_with_notification(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 0]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 50,
            'status' => 'open',
            'is_active' => true,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);
        $initialFingerprint = $component->get('dataFingerprint');

        // Initially expected cash is 50
        $this->assertEquals(50.0, (float) $component->get('stats')['expected_cash']);

        // A new direct sale invoice occurs in this shift while the page is open
        Invoice::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'invoice_no' => 'INV-001',
            'invoice_type' => 'direct_sale',
            'status' => 'posted',
            'subtotal' => 120,
            'final_amount' => 120,
            'paid_amount' => 120,
        ]);

        // Trigger polling / checkForUpdates
        $component->call('checkForUpdates')
            ->assertSet('hasNewTransactions', true)
            ->assertNotified('تنبيه: تم تسجيل عمليات جديدة بالوردية!');

        $this->assertNotEquals($initialFingerprint, $component->get('dataFingerprint'));

        // Verify stats refreshed
        $this->assertEquals(120.0, (float) $component->get('stats')['sales_amount']);
        $this->assertEquals(170.0, (float) $component->get('stats')['expected_cash']); // 50 start + 120 sales
    }

    public function test_close_prevents_closing_if_new_operations_occurred_unreviewed(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 0]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);
        $component->set('cashbox_actuals.'.$box->id, 100);

        // Before user clicks close, an expense happens in the background
        ExpenseHistory::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'cash_box_id' => $box->id,
            'amount' => 30,
            'description' => 'شراء بنزين',
        ]);
        CashBoxLedgerEntry::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'cash_box_id' => $box->id,
            'operation' => 'expense',
            'direction' => 'out',
            'amount' => 30,
        ]);

        // Attempt to close
        $component->call('close')
            ->assertSet('hasNewTransactions', true)
            ->assertNotified('تنبيه: حدثت عمليات جديدة قبل التقفيل!');

        // Shift MUST NOT be closed yet!
        $this->assertEquals('open', $shift->fresh()->status);
        $this->assertTrue((bool) $shift->fresh()->is_active);

        // Verify stats updated to reflect the new expense
        $this->assertEquals(30.0, (float) $component->get('stats')['expenses_amount']);
        $this->assertEquals(70.0, (float) $component->get('stats')['expected_cash']);

        // Now user reviews and updates actual amount if necessary, and closes
        $component->set('cashbox_actuals.'.$box->id, 70);
        $component->call('close');

        // Now shift is closed successfully
        $this->assertEquals('close', $shift->fresh()->status);
        $this->assertFalse((bool) $shift->fresh()->is_active);
        $this->assertEquals(70.0, (float) $shift->fresh()->expected_amount);
        $this->assertEquals(70.0, (float) $shift->fresh()->actual_amount);
    }

    public function test_manual_refresh_updates_stats_and_fingerprint(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 0]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);

        Invoice::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'invoice_no' => 'INV-002',
            'invoice_type' => 'device_sale',
            'status' => 'posted',
            'play_amount' => 80,
            'final_amount' => 80,
            'paid_amount' => 80,
        ]);

        $component->call('refreshStats')
            ->assertSet('hasNewTransactions', false)
            ->assertNotified('تم تحديث بيانات الوردية بنجاح');

        $this->assertEquals(80.0, (float) $component->get('stats')['playstation_amount']);
        $this->assertEquals(180.0, (float) $component->get('stats')['expected_cash']);
    }

    public function test_unpaid_debt_is_removed_from_expected_cash_without_creating_a_cash_box_outflow(): void
    {
        $branch = Branch::query()->create(['name' => 'Debt branch', 'code' => 'DEBT']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'debt@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $cashBox = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'Main', 'current_balance' => 160]);
        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);
        Invoice::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'invoice_no' => 'DEBT-1',
            'invoice_type' => 'direct_sale',
            'order_amount' => 100,
            'subtotal' => 100,
            'final_amount' => 100,
            'paid_amount' => 60,
            'remain_amount' => -40,
        ]);
        CashBoxLedgerEntry::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'cash_box_id' => $cashBox->id,
            'operation' => 'direct_sale',
            'direction' => 'in',
            'amount' => 60,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);

        $this->assertSame(40.0, (float) $component->get('stats')['credit_amount']);
        $this->assertSame(160.0, (float) $component->get('stats')['expected_cash']);
        $this->assertSame(60.0, (float) $component->get('cashBoxSummaries')[0]['net_change']);

        $shift->update(['sales_amount' => 100, 'credit_amount' => 40]);
        $this->assertSame(160.0, $shift->fresh()->remain_amount);
    }

    public function test_customer_balance_payment_does_not_change_cash_box_liquidity(): void
    {
        $branch = Branch::query()->create(['name' => 'Wallet branch', 'code' => 'WALLET']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'wallet@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $cashBox = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'Main', 'current_balance' => 160]);
        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);
        $invoice = Invoice::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'invoice_no' => 'WALLET-1',
            'invoice_type' => 'direct_sale',
            'order_amount' => 100,
            'subtotal' => 100,
            'final_amount' => 100,
            'paid_amount' => 100,
            'remain_amount' => 0,
        ]);
        DB::table('invoice_payments')->insert([
            'branch_id' => $branch->id,
            'invoice_id' => $invoice->id,
            'payment_method' => 'customer_balance',
            'amount' => 40,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        CashBoxLedgerEntry::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'cash_box_id' => $cashBox->id,
            'operation' => 'direct_sale',
            'direction' => 'in',
            'amount' => 60,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);

        $this->assertSame(40.0, (float) $component->get('stats')['customer_credit_amount']);
        $this->assertSame(160.0, (float) $component->get('stats')['expected_cash']);
        $this->assertSame(60.0, (float) $component->get('cashBoxSummaries')[0]['net_change']);
    }

    public function test_acknowledge_new_transactions_resets_flag(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 0]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);
        $component->set('hasNewTransactions', true);

        $component->call('acknowledgeNewTransactions')
            ->assertSet('hasNewTransactions', false);
    }

    public function test_check_for_updates_does_nothing_if_shift_already_closed(): void
    {
        $branch = Branch::query()->create(['name' => 'الفرع 1', 'code' => 'B1']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $box = CashBox::query()->create(['branch_id' => $branch->id, 'name' => 'الدرج الرئيسي', 'current_balance' => 0]);

        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 1,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'close',
            'is_active' => false,
        ]);

        $component = Livewire::test(CloseShift::class, ['record' => $shift->id]);
        $initialFingerprint = $component->get('dataFingerprint');

        // Add an invoice
        Invoice::query()->create([
            'branch_id' => $branch->id,
            'shift_id' => $shift->id,
            'invoice_no' => 'INV-003',
            'invoice_type' => 'direct_sale',
            'status' => 'posted',
            'subtotal' => 50,
            'final_amount' => 50,
        ]);

        $component->call('checkForUpdates')
            ->assertSet('hasNewTransactions', false);

        $this->assertEquals($initialFingerprint, $component->get('dataFingerprint'));
    }

    public function test_closing_shift_redirects_to_thermal_report_when_printing_is_enabled(): void
    {
        $branch = Branch::query()->create(['name' => 'Main Branch', 'code' => 'PRINT']);
        $user = User::query()->create(['name' => 'Admin', 'email' => 'print@test.com', 'password' => bcrypt('password')]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $cashBox = CashBox::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Main Cash Box',
            'current_balance' => 100,
        ]);
        $shift = Shift::query()->create([
            'branch_id' => $branch->id,
            'opened_by_user_id' => $user->id,
            'shift_no' => 12,
            'shift_date' => now()->toDateString(),
            'shift_from' => now(),
            'start_amount' => 100,
            'status' => 'open',
            'is_active' => true,
        ]);
        SystemConfig::set('print_active', '1', 'print');
        SystemConfig::set('print_shift_close', '1', 'print');
        SystemConfig::set('sys_invoice_printer', 'Receipt Printer', 'print');

        Livewire::test(CloseShift::class, ['record' => $shift->id])
            ->set('cashbox_actuals.'.$cashBox->id, 100)
            ->call('close')
            ->assertRedirect(route('tenant.shifts.print-close', ['shift' => $shift->id, 'auto' => 1]));

        $view = app(PrintShiftCloseController::class)($shift->fresh());
        $html = $view->render();

        $this->assertStringContainsString('تقرير تقفيل الشيفت', $html);
        $this->assertStringContainsString('Main Cash Box', $html);
        $this->assertStringContainsString('طباعة التقرير', $html);
        $this->assertStringContainsString('new WebSocket(bridgeUrl)', $html);
        $this->assertStringContainsString('Receipt Printer', $html);
        $this->assertStringContainsString('PRINT|${printerName}|${printableHtml}', $html);
        $this->assertStringContainsString('browserPrint()', $html);
    }
}
