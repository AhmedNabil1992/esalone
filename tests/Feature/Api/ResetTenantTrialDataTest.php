<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper;
use Tests\TestCase;

class ResetTenantTrialDataTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $databaseFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.tenant_provisioning.token', 'reset-test-token');
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

    public function test_transactions_are_deleted_in_dependency_order_and_master_data_is_reset(): void
    {
        $tenant = $this->createTenantDatabase();
        tenancy()->initialize($tenant);
        $this->seedTenantData();
        tenancy()->end();

        $otherTenant = $this->createTenantDatabase();
        tenancy()->initialize($otherTenant);
        $this->seedTenantData();
        tenancy()->end();

        $this->withToken('reset-test-token')
            ->postJson('/api/tenants/'.$tenant->id.'/reset-trial-data')
            ->assertOk()
            ->assertJsonPath('tenant_id', $tenant->id);

        tenancy()->initialize($tenant);

        foreach ($this->transactionTables() as $table) {
            $this->assertSame(0, DB::connection('tenant')->table($table)->count(), $table.' was not emptied.');
        }

        $this->assertSame(1, DB::connection('tenant')->table('users')->count());
        $this->assertSame('active', DB::connection('tenant')->table('devices')->value('status'));
        $this->assertEquals(0, DB::connection('tenant')->table('warehouse_stocks')->value('quantity'));
        $this->assertEquals(0, DB::connection('tenant')->table('warehouse_stocks')->value('amount'));
        $this->assertEquals(100, DB::connection('tenant')->table('cash_boxes')->value('current_balance'));
        $this->assertEquals(0, DB::connection('tenant')->table('customers')->value('balance'));
        $this->assertEquals(0, DB::connection('tenant')->table('customers')->value('points'));

        tenancy()->end();
        tenancy()->initialize($otherTenant);

        $this->assertSame(1, DB::connection('tenant')->table('invoices')->count());
        $this->assertSame('busy', DB::connection('tenant')->table('devices')->value('status'));
        $this->assertEquals(25, DB::connection('tenant')->table('warehouse_stocks')->value('quantity'));
        $this->assertEquals(750, DB::connection('tenant')->table('cash_boxes')->value('current_balance'));
        $this->assertEquals(45, DB::connection('tenant')->table('customers')->value('balance'));
    }

    public function test_reset_requires_a_valid_token_and_ready_tenant(): void
    {
        $tenant = $this->createTenantDatabase();

        $this->withToken('wrong-token')
            ->postJson('/api/tenants/'.$tenant->id.'/reset-trial-data')
            ->assertUnauthorized();

        $tenant->update(['provisioning_status' => 'provisioning']);

        $this->withToken('reset-test-token')
            ->postJson('/api/tenants/'.$tenant->id.'/reset-trial-data')
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'TENANT_BUSY');
    }

    private function createTenantDatabase(): Tenant
    {
        $databaseName = 'reset-trial-test-'.Str::uuid().'.sqlite';
        $databaseFile = database_path($databaseName);
        $this->databaseFiles[] = $databaseFile;
        touch($databaseFile);

        $tenant = Tenant::withoutEvents(fn (): Tenant => Tenant::create([
            'id' => (string) Str::ulid(),
            'is_active' => true,
            'provisioning_status' => 'ready',
        ]));
        $tenant->setInternal('db_name', $databaseName);
        $tenant->save();
        tenancy()->initialize($tenant);

        $this->createTenantSchema();
        tenancy()->end();

        return $tenant;
    }

    private function createTenantSchema(): void
    {
        $schema = Schema::connection('tenant');
        $schema->create('users', fn (Blueprint $table) => $table->id());
        $schema->create('devices', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->timestamps();
        });
        $schema->create('warehouse_stocks', function (Blueprint $table): void {
            $table->id();
            $table->decimal('quantity', 12, 3);
            $table->decimal('amount', 12, 4);
            $table->timestamps();
        });
        $schema->create('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('current_balance', 12, 2);
            $table->timestamps();
        });
        $schema->create('customers', function (Blueprint $table): void {
            $table->id();
            $table->decimal('balance', 12, 2);
            $table->decimal('points', 12, 2);
            $table->timestamps();
        });
        $schema->create('shifts', fn (Blueprint $table) => $table->id());
        $schema->create('play_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts');
        });
        $schema->create('cash_box_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts');
        });
        $schema->create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('play_session_id')->constrained('play_sessions');
            $table->foreignId('shift_id')->constrained('shifts');
        });

        foreach (['tax_histories', 'service_histories', 'discount_histories'] as $table) {
            $schema->create($table, function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->foreignId('invoice_id')->constrained('invoices');
                $blueprint->foreignId('ledger_entry_id')->constrained('cash_box_ledger_entries');
            });
        }

        $schema->create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices');
        });
        $schema->create('purchases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts');
        });
        $schema->create('purchase_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases');
        });
        $schema->create('stock_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases');
            $table->foreignId('purchase_item_id')->constrained('purchase_items');
            $table->foreignId('invoice_id')->constrained('invoices');
        });

        foreach ($this->simpleTransactionTables() as $table) {
            $schema->create($table, fn (Blueprint $blueprint) => $blueprint->id());
        }
    }

    private function seedTenantData(): void
    {
        $database = DB::connection('tenant');
        $now = now();
        $database->table('users')->insert(['id' => 1]);
        $database->table('devices')->insert(['status' => 'busy', 'created_at' => $now, 'updated_at' => $now]);
        $database->table('warehouse_stocks')->insert(['quantity' => 25, 'amount' => 500, 'created_at' => $now, 'updated_at' => $now]);
        $database->table('cash_boxes')->insert(['opening_balance' => 100, 'current_balance' => 750, 'created_at' => $now, 'updated_at' => $now]);
        $database->table('customers')->insert(['balance' => 45, 'points' => 12, 'created_at' => $now, 'updated_at' => $now]);

        $shiftId = $database->table('shifts')->insertGetId([]);
        $sessionId = $database->table('play_sessions')->insertGetId(['shift_id' => $shiftId]);
        $ledgerId = $database->table('cash_box_ledger_entries')->insertGetId(['shift_id' => $shiftId]);
        $invoiceId = $database->table('invoices')->insertGetId(['play_session_id' => $sessionId, 'shift_id' => $shiftId]);
        $purchaseId = $database->table('purchases')->insertGetId(['shift_id' => $shiftId]);
        $purchaseItemId = $database->table('purchase_items')->insertGetId(['purchase_id' => $purchaseId]);

        foreach (['tax_histories', 'service_histories', 'discount_histories'] as $table) {
            $database->table($table)->insert(['invoice_id' => $invoiceId, 'ledger_entry_id' => $ledgerId]);
        }

        $database->table('invoice_payments')->insert(['invoice_id' => $invoiceId]);
        $database->table('stock_movements')->insert([
            'purchase_id' => $purchaseId,
            'purchase_item_id' => $purchaseItemId,
            'invoice_id' => $invoiceId,
        ]);

        foreach ($this->simpleTransactionTables() as $table) {
            $database->table($table)->insert(['id' => 1]);
        }
    }

    /** @return array<int, string> */
    private function simpleTransactionTables(): array
    {
        return [
            'agent_conversation_messages', 'agent_conversations', 'whatsapp_messages', 'whatsapp_webhooks',
            'market_history', 'play_history', 'order_session_details', 'play_session_details', 'customer_credits',
            'reservations', 'waiting_lists', 'device_control_commands', 'expense_histories', 'notifications',
        ];
    }

    /** @return array<int, string> */
    private function transactionTables(): array
    {
        return [
            'agent_conversation_messages', 'agent_conversations', 'whatsapp_messages', 'whatsapp_webhooks',
            'tax_histories', 'service_histories', 'discount_histories', 'invoice_payments', 'stock_movements',
            'market_history', 'play_history', 'invoices', 'order_session_details', 'play_session_details',
            'play_sessions', 'purchase_items', 'purchases', 'customer_credits', 'reservations', 'waiting_lists',
            'device_control_commands', 'expense_histories', 'cash_box_ledger_entries', 'shifts', 'notifications',
        ];
    }
}
