<?php

namespace Tests\Unit;

use App\Actions\Tenant\ManageSessionOrderAction;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class ManageSessionOrderActionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private bool $createdPlaySessionsTable = false;

    private bool $createdOrderSessionDetailsTable = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('play_sessions')) {
            Schema::create('play_sessions', function (Blueprint $table): void {
                $table->id();
                $table->string('status');
                $table->timestamps();
            });
            $this->createdPlaySessionsTable = true;
        }

        if (! Schema::hasTable('order_session_details')) {
            Schema::create('order_session_details', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('play_session_id');
                $table->decimal('price', 8, 2);
                $table->integer('quantity');
                $table->decimal('total', 8, 2);
                $table->string('status');
                $table->boolean('printed')->default(false);
                $table->timestamps();
            });
            $this->createdOrderSessionDetailsTable = true;
        }
    }

    protected function tearDown(): void
    {
        if ($this->createdOrderSessionDetailsTable) {
            Schema::dropIfExists('order_session_details');
        }

        if ($this->createdPlaySessionsTable) {
            Schema::dropIfExists('play_sessions');
        }

        parent::tearDown();
    }

    public function test_update_quantity_requires_device_control_update_permission(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('can')
            ->once()
            ->with(ManageSessionOrderAction::UPDATE_PERMISSION)
            ->andReturnFalse();

        $this->expectException(AuthorizationException::class);

        (new ManageSessionOrderAction)->updateQuantity(1, 2, $actor);
    }

    public function test_delete_requires_device_control_delete_permission(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('can')
            ->once()
            ->with(ManageSessionOrderAction::DELETE_PERMISSION)
            ->andReturnFalse();

        $this->expectException(AuthorizationException::class);

        (new ManageSessionOrderAction)->delete(1, $actor);
    }

    public function test_quantity_must_be_at_least_one_after_authorization(): void
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('can')
            ->once()
            ->with(ManageSessionOrderAction::UPDATE_PERMISSION)
            ->andReturnTrue();

        $this->expectException(InvalidArgumentException::class);

        (new ManageSessionOrderAction)->updateQuantity(1, 0, $actor);
    }

    public function test_update_recalculates_total_for_pending_order_in_active_session(): void
    {
        [$actor, $orderId] = $this->createAuthorizedOrder(ManageSessionOrderAction::UPDATE_PERMISSION);

        $order = (new ManageSessionOrderAction)->updateQuantity($orderId, 3, $actor);

        $this->assertSame(3, $order->quantity);
        $this->assertSame(37.5, (float) $order->total);
        $this->assertFalse((bool) $order->printed);
    }

    public function test_delete_removes_pending_order_in_active_session(): void
    {
        [$actor, $orderId] = $this->createAuthorizedOrder(ManageSessionOrderAction::DELETE_PERMISSION);

        (new ManageSessionOrderAction)->delete($orderId, $actor);

        $this->assertFalse(DB::table('order_session_details')->where('id', $orderId)->exists());
    }

    /** @return array{0: User, 1: int} */
    private function createAuthorizedOrder(string $permission): array
    {
        $actor = Mockery::mock(User::class);
        $actor->shouldReceive('can')->once()->with($permission)->andReturnTrue();

        $sessionId = DB::table('play_sessions')->insertGetId([
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = DB::table('order_session_details')->insertGetId([
            'play_session_id' => $sessionId,
            'price' => 12.50,
            'quantity' => 2,
            'total' => 25,
            'status' => 'pending',
            'printed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$actor, $orderId];
    }
}
