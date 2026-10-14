<?php

namespace Tests\Unit;

use App\Actions\Tenant\CreateDirectSaleAction;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\MarketHistory;
use App\Models\Tenant\ProductComponent;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CreateDirectSaleActionTest extends TestCase
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
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('points', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('shifts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedInteger('shift_no')->nullable()->unique();
            $table->string('status')->default('open');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
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

        $this->ensureTable('customer_credits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('inventory_groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('catalog_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('inventory_group_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->boolean('is_product')->default(true);
            $table->boolean('is_component')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('product_components', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('component_id');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->string('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->ensureTable('invoices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('invoice_no')->unique();
            $table->string('invoice_type')->default('device_sale');
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('cash_box_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->decimal('play_amount', 12, 2)->default(0);
            $table->decimal('order_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->decimal('service_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('final_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('remain_amount', 12, 2)->default(0);
            $table->string('status')->default('posted');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('discount_histories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->string('invoice_no');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('reason')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('ledger_entry_id')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('invoice_id');
            $table->unsignedBigInteger('cash_box_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('payment_method')->default('cash');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('market_history', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('catalog_item_id')->nullable();
            $table->string('subject_name')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->date('transaction_date')->nullable();
            $table->time('transaction_time')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedInteger('shift_no')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        $this->ensureTable('warehouses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('warehouse_stocks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('catalog_item_id')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('min_quantity', 12, 3)->default(0);
            $table->timestamps();
        });

        $this->ensureTable('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->unsignedBigInteger('catalog_item_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('invoice_no')->nullable();
            $table->string('invoice_type')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('movement_type')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });

        // Insert default system config for service and tax percent
        if (! Schema::hasTable('system_configs') || DB::table('system_configs')->count() === 0) {
            DB::table('system_configs')->insert([
                ['key' => 'sys_service_percent', 'value' => '0', 'group' => 'system'],
                ['key' => 'sys_tax_percent', 'value' => '0', 'group' => 'system'],
                ['key' => 'sys_service_on_market_invoices', 'value' => '0', 'group' => 'system'],
                ['key' => 'sys_tax_on_market_invoices', 'value' => '0', 'group' => 'system'],
                ['key' => 'sys_customer_points_ratio', 'value' => '10', 'group' => 'system'],
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Explicitly truncate all tables in reverse order to respect foreign keys
        Schema::disableForeignKeyConstraints();

        foreach (['stock_movements', 'warehouse_stocks', 'warehouses', 'market_history', 'discount_histories', 'invoice_payments', 'customer_credits', 'cash_box_ledger_entries', 'invoices', 'product_components', 'catalog_items', 'inventory_groups', 'customers', 'shifts', 'cash_boxes', 'users', 'system_configs', 'branches'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        Schema::enableForeignKeyConstraints();

        parent::tearDown();
    }

    /**
     * @param  callable(Blueprint): void  $callback
     */
    private function ensureTable(string $tableName, callable $callback): void
    {
        if (Schema::hasTable($tableName)) {
            Schema::disableForeignKeyConstraints();
            Schema::drop($tableName);
            Schema::enableForeignKeyConstraints();
        }
        Schema::create($tableName, $callback);
        $this->createdTables[] = $tableName;
    }

    public function test_create_direct_sale(): void
    {
        // Create required models
        $uniqueId = (string) Str::uuid();
        $user = User::create([
            'name' => 'Test User',
            'email' => "test-{$uniqueId}@example.com",
            'password' => bcrypt('password'),
        ]);
        $shift = Shift::create([
            'shift_no' => random_int(1000, 9999),
            'status' => 'open',
            'is_active' => true,
        ]);

        // Create warehouse
        DB::table('warehouses')->insert([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $warehouse = DB::table('warehouses')->first();

        $cashBox = CashBox::create([
            'name' => 'Cash Box 1',
            'is_active' => true,
            'current_balance' => 0,
        ]);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'balance' => 0,
            'is_active' => true,
        ]);
        $group = InventoryGroup::create([
            'name' => 'Test Group',
            'is_active' => true,
        ]);

        // Create catalog items
        $item1 = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'Item 1',
            'sale_price' => 100.00,
            'purchase_price' => 80.00,
            'is_product' => true,
        ]);
        $item2 = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'Item 2',
            'sale_price' => 50.00,
            'purchase_price' => 40.00,
            'is_product' => true,
        ]);

        // Create warehouse stocks
        DB::table('warehouse_stocks')->insert([
            ['warehouse_id' => $warehouse->id, 'catalog_item_id' => $item1->id, 'quantity' => 100, 'min_quantity' => 10],
            ['warehouse_id' => $warehouse->id, 'catalog_item_id' => $item2->id, 'quantity' => 50, 'min_quantity' => 5],
        ]);

        // Execute action
        $action = app(CreateDirectSaleAction::class);
        $authorizedUser = Mockery::mock($user)->makePartial();
        $authorizedUser->shouldReceive('can')
            ->once()
            ->with(CreateDirectSaleAction::DISCOUNT_PERMISSION)
            ->andReturnTrue();
        $result = $action->execute(
            items: [
                [
                    'catalog_item_id' => $item1->id,
                    'quantity' => 2,
                    'unit_price' => 100.00,
                    'total_amount' => 200.00,
                ],
                [
                    'catalog_item_id' => $item2->id,
                    'quantity' => 1,
                    'unit_price' => 50.00,
                    'total_amount' => 50.00,
                ],
            ],
            customerId: $customer->id,
            cashBoxId: $cashBox->id,
            discountAmount: 10.00,
            actor: $authorizedUser
        );

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('invoice_no', $result);
        $this->assertArrayHasKey('total_amount', $result);
        $this->assertArrayHasKey('items_count', $result);
        $this->assertEquals(2, $result['items_count']);

        // Verify invoice was created
        $invoice = Invoice::where('invoice_no', $result['invoice_no'])->first();
        $this->assertNotNull($invoice);
        $this->assertSame('direct_sale', $invoice->invoice_type);
        $this->assertSame($cashBox->id, $invoice->cash_box_id);
        $this->assertEquals(250.00, $invoice->subtotal);
        $this->assertEquals(10.00, $invoice->discount_amount);
        $this->assertEquals(240.00, $invoice->final_amount);
        $this->assertEquals(240.00, $invoice->paid_amount);
        $this->assertEquals(0.00, $invoice->remain_amount);

        // Verify market history entries
        $marketHistories = MarketHistory::where('invoice_no', $result['invoice_no'])->get();
        $this->assertCount(2, $marketHistories);

        // Verify stock movements were recorded
        $stockMovements = DB::table('stock_movements')->where('invoice_no', $result['invoice_no'])->get();
        $this->assertCount(2, $stockMovements);

        // Check stock movement details
        $movement1 = $stockMovements->where('catalog_item_id', $item1->id)->first();
        $this->assertNotNull($movement1);
        $this->assertEquals(-2, $movement1->quantity);
        $this->assertEquals('direct_sale', $movement1->movement_type);
        $this->assertEquals('direct_sale', $movement1->invoice_type);

        $movement2 = $stockMovements->where('catalog_item_id', $item2->id)->first();
        $this->assertNotNull($movement2);
        $this->assertEquals(-1, $movement2->quantity);

        // Verify warehouse stock quantities were decremented
        $stock1 = DB::table('warehouse_stocks')
            ->where('warehouse_id', $warehouse->id)
            ->where('catalog_item_id', $item1->id)
            ->first();
        $this->assertEquals(98, $stock1->quantity); // 100 - 2

        $stock2 = DB::table('warehouse_stocks')
            ->where('warehouse_id', $warehouse->id)
            ->where('catalog_item_id', $item2->id)
            ->first();
        $this->assertEquals(49, $stock2->quantity); // 50 - 1

        $this->assertSame('240.00', $cashBox->fresh()->current_balance);
        $this->assertEquals(
            240.00,
            (float) DB::table('cash_box_ledger_entries')
                ->where('reference_type', Invoice::class)
                ->where('reference_id', $invoice->id)
                ->selectRaw("SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_amount")
                ->value('net_amount'),
        );
        $this->assertDatabaseMissing('cash_box_ledger_entries', [
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
            'operation' => 'direct_sale_discount',
        ]);
        $this->assertSame('24.00', $customer->fresh()->points);
        $this->assertSame('0.00', $customer->fresh()->balance);
        $this->assertDatabaseCount('customer_credits', 0);

        $payment = DB::table('invoice_payments')->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals($cashBox->id, $payment->cash_box_id);
        $this->assertEquals(240.00, (float) $payment->amount);
        $this->assertSame('cash', $payment->payment_method);
    }

    public function test_sale_of_a_service_product_deducts_its_configured_materials(): void
    {
        $user = User::create([
            'name' => 'Salon Manager',
            'email' => 'salon-manager@example.test',
            'password' => bcrypt('password'),
        ]);
        Shift::create([
            'branch_id' => 1,
            'shift_no' => 1001,
            'status' => 'open',
            'is_active' => true,
        ]);
        DB::table('warehouses')->insert([
            'branch_id' => 1,
            'name' => 'Salon Warehouse',
            'code' => 'SALON-WH',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $warehouse = DB::table('warehouses')->first();
        $cashBox = CashBox::create([
            'branch_id' => 1,
            'name' => 'Salon Cash Box',
            'code' => 'SALON-CASH',
            'is_active' => true,
            'current_balance' => 0,
        ]);
        $component = CatalogItem::create([
            'name' => 'Hair color material',
            'purchase_price' => 20,
            'is_product' => false,
            'is_component' => true,
            'is_active' => true,
        ]);
        $serviceItem = CatalogItem::create([
            'name' => 'Hair color service',
            'sale_price' => 200,
            'purchase_price' => 0,
            'is_product' => true,
            'is_component' => false,
            'is_active' => true,
        ]);
        ProductComponent::create([
            'product_id' => $serviceItem->getKey(),
            'component_id' => $component->getKey(),
            'quantity' => 0.25,
        ]);
        DB::table('warehouse_stocks')->insert([
            'warehouse_id' => $warehouse->id,
            'catalog_item_id' => $component->getKey(),
            'quantity' => 10,
            'amount' => 200,
            'min_quantity' => 1,
        ]);

        $result = app(CreateDirectSaleAction::class)->execute(
            items: [[
                'catalog_item_id' => (int) $serviceItem->getKey(),
                'quantity' => 2,
                'unit_price' => 200,
                'total_amount' => 400,
            ]],
            cashBoxId: (int) $cashBox->getKey(),
            actor: $user,
        );

        $remainingStock = DB::table('warehouse_stocks')
            ->where('warehouse_id', $warehouse->id)
            ->where('catalog_item_id', $component->getKey())
            ->value('quantity');
        $movement = DB::table('stock_movements')
            ->where('invoice_no', $result['invoice_no'])
            ->where('catalog_item_id', $component->getKey())
            ->first();

        $this->assertEquals(9.5, (float) $remainingStock);
        $this->assertNotNull($movement);
        $this->assertEquals(-0.5, (float) $movement->quantity);
        $this->assertSame('direct_sale', $movement->invoice_type);
    }

    public function test_direct_sale_allows_warehouse_stock_to_become_negative(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'negative-stock@example.com',
            'password' => bcrypt('password'),
        ]);
        Shift::create([
            'shift_no' => random_int(1000, 9999),
            'status' => 'open',
            'is_active' => true,
        ]);
        $cashBox = CashBox::create([
            'name' => 'Cash Box 1',
            'is_active' => true,
            'current_balance' => 0,
        ]);
        $group = InventoryGroup::create([
            'name' => 'Test Group',
            'is_active' => true,
        ]);
        $item = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'Out of stock item',
            'sale_price' => 50.00,
            'purchase_price' => 30.00,
            'is_product' => true,
        ]);

        $warehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('warehouse_stocks')->insert([
            'warehouse_id' => $warehouseId,
            'catalog_item_id' => $item->id,
            'quantity' => 0,
            'amount' => 0,
            'min_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(CreateDirectSaleAction::class)->execute(
            items: [[
                'catalog_item_id' => $item->id,
                'quantity' => 2,
                'unit_price' => 50.00,
                'total_amount' => 100.00,
            ]],
            cashBoxId: $cashBox->id,
            actor: $user,
        );

        $this->assertDatabaseHas('warehouse_stocks', [
            'warehouse_id' => $warehouseId,
            'catalog_item_id' => $item->id,
            'quantity' => -2,
            'amount' => 0,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'invoice_no' => $result['invoice_no'],
            'catalog_item_id' => $item->id,
            'quantity' => -2,
        ]);
    }

    public function test_manual_discount_requires_device_control_discount_permission(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('can')
            ->once()
            ->with(CreateDirectSaleAction::DISCOUNT_PERMISSION)
            ->andReturnFalse();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('غير مصرح لك بإضافة خصم.');

        app(CreateDirectSaleAction::class)->execute(
            items: [[
                'catalog_item_id' => 1,
                'quantity' => 1,
                'unit_price' => 100,
                'total_amount' => 100,
            ]],
            discountAmount: 10,
            actor: $actor,
        );
    }

    public function test_overpayment_saved_as_customer_credit_is_fully_added_to_the_cash_box(): void
    {
        $uniqueId = (string) Str::uuid();
        $user = User::create([
            'name' => 'Test User',
            'email' => "test-overpay-{$uniqueId}@example.com",
            'password' => bcrypt('password'),
        ]);
        Shift::create([
            'shift_no' => random_int(1000, 9999),
            'status' => 'open',
            'is_active' => true,
        ]);

        $cashBox = CashBox::create([
            'name' => 'Cash Box 1',
            'is_active' => true,
            'current_balance' => 0,
        ]);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'balance' => 0,
            'points' => 0,
            'is_active' => true,
        ]);
        $group = InventoryGroup::create([
            'name' => 'Test Group',
            'is_active' => true,
        ]);
        $item = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'Item 1',
            'sale_price' => 75.00,
            'purchase_price' => 50.00,
            'is_product' => true,
        ]);

        $result = app(CreateDirectSaleAction::class)->execute(
            items: [[
                'catalog_item_id' => $item->id,
                'quantity' => 1,
                'unit_price' => 75.00,
                'total_amount' => 75.00,
            ]],
            customerId: $customer->id,
            cashBoxId: $cashBox->id,
            actor: $user,
            paidAmount: 100.00,
            depositRemainderToCustomerBalance: true,
        );

        $invoice = Invoice::where('invoice_no', $result['invoice_no'])->firstOrFail();

        $this->assertEquals(75.00, $invoice->final_amount);
        $this->assertEquals(75.00, $invoice->paid_amount);
        $this->assertEquals(0.00, $invoice->remain_amount);
        $this->assertSame('100.00', $cashBox->fresh()->current_balance);
        $this->assertSame('25.00', $customer->fresh()->balance);
        $this->assertSame('7.50', $customer->fresh()->points);

        $payment = DB::table('invoice_payments')->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(75.00, (float) $payment->amount);

        $credit = DB::table('customer_credits')->first();
        $this->assertNotNull($credit);
        $this->assertSame('deposit', $credit->type);
        $this->assertEquals(25.00, (float) $credit->amount);

        $this->assertEquals(
            100.00,
            (float) DB::table('cash_box_ledger_entries')
                ->where('reference_type', Invoice::class)
                ->where('reference_id', $invoice->id)
                ->selectRaw("SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as net_amount")
                ->value('net_amount'),
        );
    }

    public function test_throws_exception_when_no_items_provided(): void
    {
        $uniqueId = (string) Str::uuid();
        $user = User::create([
            'name' => 'Test User',
            'email' => "test-{$uniqueId}@example.com",
            'password' => bcrypt('password'),
        ]);
        $action = app(CreateDirectSaleAction::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('يجب اختيار صنف واحد على الأقل.');

        $action->execute(
            items: [],
            actor: $user
        );
    }

    public function test_throws_exception_when_no_active_shift(): void
    {
        $uniqueId = (string) Str::uuid();
        $user = User::create([
            'name' => 'Test User',
            'email' => "test-{$uniqueId}@example.com",
            'password' => bcrypt('password'),
        ]);
        $cashBox = CashBox::create([
            'name' => 'Cash Box 1',
            'is_active' => true,
            'current_balance' => 0,
        ]);
        $group = InventoryGroup::create([
            'name' => 'Test Group',
            'is_active' => true,
        ]);
        $item = CatalogItem::create([
            'inventory_group_id' => $group->id,
            'name' => 'Item 1',
            'sale_price' => 100.00,
            'purchase_price' => 80.00,
            'is_product' => true,
        ]);

        $action = app(CreateDirectSaleAction::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('لا توجد فترة عمل نشطة.');

        $action->execute(
            items: [
                [
                    'catalog_item_id' => $item->id,
                    'quantity' => 1,
                    'unit_price' => 100.00,
                    'total_amount' => 100.00,
                ],
            ],
            actor: $user
        );
    }
}
