<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\TransferCashBetweenCashBoxesAction;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class TransferCashBetweenCashBoxesActionTest extends TestCase
{
    /** @var array<int, string> */
    private array $createdTables = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureTable('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        $this->ensureTable('cash_boxes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('type')->default('cash');
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('current_balance', 12, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->ensureTable('cash_box_ledger_entries', function (Blueprint $table): void {
            $table->id();
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

        $this->ensureTable('shifts', function (Blueprint $table): void {
            $table->id();
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

    public function test_transfer_updates_both_cash_boxes_and_creates_ledger_entries(): void
    {
        $unique = uniqid('transfer_', true);

        $actor = User::query()->create([
            'name' => 'Tester',
            'email' => $unique.'@example.com',
            'password' => 'password',
        ]);

        $fromCashBox = CashBox::query()->create([
            'name' => 'Main',
            'code' => $unique.'_main',
            'current_balance' => 150,
            'is_active' => true,
        ]);

        $toCashBox = CashBox::query()->create([
            'name' => 'Secondary',
            'code' => $unique.'_sec',
            'current_balance' => 10,
            'is_active' => true,
        ]);

        app(TransferCashBetweenCashBoxesAction::class)->execute([
            'from_cash_box_id' => $fromCashBox->id,
            'to_cash_box_id' => $toCashBox->id,
            'amount' => 40,
            'description' => 'Manual transfer',
        ], $actor);

        $this->assertSame('110.00', $fromCashBox->fresh()->current_balance);
        $this->assertSame('50.00', $toCashBox->fresh()->current_balance);

        $entries = DB::table('cash_box_ledger_entries')
            ->where('operation', 'transfer')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $entries);
        $this->assertSame('out', $entries[0]->direction);
        $this->assertSame('in', $entries[1]->direction);
    }

    public function test_transfer_fails_when_source_balance_is_insufficient(): void
    {
        $unique = uniqid('transfer_fail_', true);

        $actor = User::query()->create([
            'name' => 'Tester',
            'email' => $unique.'@example.com',
            'password' => 'password',
        ]);

        $fromCashBox = CashBox::query()->create([
            'name' => 'Main',
            'code' => $unique.'_main',
            'current_balance' => 10,
            'is_active' => true,
        ]);

        $toCashBox = CashBox::query()->create([
            'name' => 'Secondary',
            'code' => $unique.'_sec',
            'current_balance' => 50,
            'is_active' => true,
        ]);

        $this->expectException(RuntimeException::class);

        app(TransferCashBetweenCashBoxesAction::class)->execute([
            'from_cash_box_id' => $fromCashBox->id,
            'to_cash_box_id' => $toCashBox->id,
            'amount' => 20,
        ], $actor);
    }
}
