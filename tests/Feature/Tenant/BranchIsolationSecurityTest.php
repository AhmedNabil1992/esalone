<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchIsolationSecurityTest extends TestCase
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
            $table->foreignId('user_id')->nullable();
            $table->string('shift_no')->nullable();
            $table->string('status')->default('open');
            $table->decimal('start_amount', 11, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            $table->string('invoice_no')->nullable();
            $table->decimal('total_amount', 11, 2)->default(0);
            $table->string('status')->default('posted');
            $table->timestamps();
        });

        Role::create(['name' => 'owner', 'guard_name' => 'web']);
        Role::create(['name' => 'cashier', 'guard_name' => 'web']);
    }

    private function tearDownDatabaseSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('branch_user');
        Schema::dropIfExists('users');
        Schema::dropIfExists('branches');
        Schema::enableForeignKeyConstraints();
    }

    public function test_cashier_user_is_strictly_restricted_to_assigned_branch_a(): void
    {
        BranchScope::resetCache();

        $branchA = Branch::query()->create(['name' => 'الفرع الرئيسي', 'code' => 'BR-001']);
        $branchB = Branch::query()->create(['name' => 'فرع العاشر', 'code' => 'BR-002']);

        $cashier = User::query()->create([
            'name' => 'كاشير الفرع الرئيسي',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
        ]);

        $cashier->assignRole('cashier');
        $cashier->branches()->attach($branchA->id);

        $shiftA = Shift::query()->create(['branch_id' => $branchA->id, 'start_amount' => 500]);
        $shiftB = Shift::query()->create(['branch_id' => $branchB->id, 'start_amount' => 1000]);

        $invoiceA = Invoice::query()->create(['branch_id' => $branchA->id, 'total_amount' => 150]);
        $invoiceB = Invoice::query()->create(['branch_id' => $branchB->id, 'total_amount' => 300]);

        $this->actingAs($cashier);
        session(['active_branch_id' => $branchA->id]);
        BranchScope::resetCache();

        $visibleInvoices = Invoice::query()->get();
        $visibleShifts = Shift::query()->get();

        $this->assertCount(1, $visibleInvoices);
        $this->assertEquals($invoiceA->id, $visibleInvoices->first()->id);

        $this->assertCount(1, $visibleShifts);
        $this->assertEquals($shiftA->id, $visibleShifts->first()->id);

        $this->assertNull(Invoice::query()->whereKey($invoiceB->id)->first());
        $this->assertNull(Shift::query()->whereKey($shiftB->id)->first());
    }

    public function test_owner_user_can_access_both_branches_in_aggregated_view(): void
    {
        BranchScope::resetCache();

        $branchA = Branch::query()->create(['name' => 'الفرع الرئيسي', 'code' => 'BR-001']);
        $branchB = Branch::query()->create(['name' => 'فرع العاشر', 'code' => 'BR-002']);

        $owner = User::query()->create([
            'name' => 'صاحب المحل',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
        ]);

        $owner->assignRole('owner');
        $owner->branches()->attach([$branchA->id, $branchB->id]);

        Invoice::query()->create(['branch_id' => $branchA->id, 'total_amount' => 150]);
        Invoice::query()->create(['branch_id' => $branchB->id, 'total_amount' => 300]);

        Shift::query()->create(['branch_id' => $branchA->id, 'start_amount' => 500]);
        Shift::query()->create(['branch_id' => $branchB->id, 'start_amount' => 1000]);

        $this->actingAs($owner);
        session(['active_branch_id' => 'all']);
        BranchScope::resetCache();

        $allInvoices = Invoice::query()->get();
        $allShifts = Shift::query()->get();

        $this->assertCount(2, $allInvoices);
        $this->assertCount(2, $allShifts);
    }

    public function test_unassigned_non_owner_user_gets_fail_safe_empty_results(): void
    {
        BranchScope::resetCache();

        $branchA = Branch::query()->create(['name' => 'الفرع الرئيسي', 'code' => 'BR-001']);

        $unassignedUser = User::query()->create([
            'name' => 'مستخدم غير مسند',
            'email' => 'unassigned@test.com',
            'password' => bcrypt('password'),
        ]);

        Invoice::query()->create(['branch_id' => $branchA->id, 'total_amount' => 150]);
        Shift::query()->create(['branch_id' => $branchA->id, 'start_amount' => 500]);

        $this->actingAs($unassignedUser);
        session(['active_branch_id' => null]);
        BranchScope::resetCache();

        $this->assertCount(0, Invoice::query()->get());
        $this->assertCount(0, Shift::query()->get());
    }
}
