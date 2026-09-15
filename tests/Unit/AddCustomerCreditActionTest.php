<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\AddCustomerCreditAction;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\Customer;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AddCustomerCreditActionTest extends TestCase
{
    /** @var array<int, string> */
    private array $createdTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTable('branches', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('branches')->insert([
            'name' => 'Main Branch',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->ensureTable('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('customers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->decimal('balance', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('drawer');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('customer_credits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('cash_box_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('cash_box_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('direction');
            $table->string('operation');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        $this->ensureTable('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedInteger('shift_no')->nullable();
            $table->string('status')->default('open');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->createdTables) as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    private function ensureTable(string $name, callable $callback): void
    {
        if (Schema::hasTable($name)) {
            return;
        }

        Schema::create($name, $callback);
        $this->createdTables[] = $name;
    }

    public function test_deposit_updates_balances_and_creates_ledger_entry(): void
    {
        $unique = uniqid('deposit_', true);

        $actor = User::query()->create([
            'name' => 'Tester',
            'email' => $unique.'@example.com',
            'password' => 'password',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Customer A',
            'balance' => 0,
            'is_active' => true,
        ]);

        $cashBox = CashBox::query()->create([
            'name' => 'Main',
            'code' => $unique,
            'current_balance' => 100,
            'is_active' => true,
        ]);

        SystemConfig::set('sys-default-cashbox', (string) $cashBox->id);

        $credit = app(AddCustomerCreditAction::class)->execute([
            'customer_id' => $customer->id,
            'type' => 'deposit',
            'amount' => 50,
            'description' => 'Customer deposit',
        ], $actor);

        $this->assertSame('deposit', $credit->type);
        $this->assertSame('50.00', $customer->fresh()->balance);
        $this->assertSame('150.00', $cashBox->fresh()->current_balance);

        $ledger = DB::table('cash_box_ledger_entries')->where('reference_id', $credit->id)->first();

        $this->assertNotNull($ledger);
        $this->assertSame('in', $ledger->direction);
        $this->assertSame('customer_credit_deposit', $ledger->operation);
    }

    public function test_withdraw_updates_balances_and_creates_ledger_entry(): void
    {
        $unique = uniqid('withdraw_', true);

        $actor = User::query()->create([
            'name' => 'Tester',
            'email' => $unique.'@example.com',
            'password' => 'password',
        ]);

        $customer = Customer::query()->create([
            'name' => 'Customer B',
            'balance' => 80,
            'is_active' => true,
        ]);

        $cashBox = CashBox::query()->create([
            'name' => 'Main',
            'code' => $unique,
            'current_balance' => 100,
            'is_active' => true,
        ]);

        $credit = app(AddCustomerCreditAction::class)->execute([
            'customer_id' => $customer->id,
            'cash_box_id' => $cashBox->id,
            'type' => 'withdraw',
            'amount' => 30,
            'description' => 'Customer withdraw',
        ], $actor);

        $this->assertSame('withdraw', $credit->type);
        $this->assertSame('50.00', $customer->fresh()->balance);
        $this->assertSame('70.00', $cashBox->fresh()->current_balance);

        $ledger = DB::table('cash_box_ledger_entries')->where('reference_id', $credit->id)->first();

        $this->assertNotNull($ledger);
        $this->assertSame('out', $ledger->direction);
        $this->assertSame('customer_credit_withdraw', $ledger->operation);
    }
}
