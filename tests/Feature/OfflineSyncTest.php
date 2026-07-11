<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlayType;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenant and initialize
        $this->tenant = Tenant::create(['id' => 'test']);
        $this->tenant->domains()->create(['domain' => 'test.ps-web.test']);
        tenancy()->initialize($this->tenant);

        // Disable CSRF for tests that use full domain requests
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_offline_init_returns_authorized_initial_state(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        // Access the init endpoint
        $response = $this->get('http://test.ps-web.test/tenant/offline/init');

        $response->assertOk()
            ->assertJsonStructure([
                'schemas',
                'data' => [
                    'devices',
                    'catalog_items',
                    'play_types',
                    'customers',
                    'reservations',
                    'waiting_lists',
                    'cash_boxes',
                ],
                'server_timestamp',
                'subscription_expiry',
            ]);
    }

    public function test_offline_sync_processes_operations(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        // Set up active shift
        Shift::create([
            'shift_no' => 1,
            'opened_by_user_id' => $user->id,
            'shift_date' => today()->toDateString(),
            'shift_from' => now()->toDateTimeString(),
            'start_amount' => 0,
            'actual_amount' => 0,
            'status' => 'open',
            'is_active' => true,
            'notify' => false,
        ]);

        // Create type
        $type = DevicesType::create([
            'name' => 'PS5 Type',
        ]);

        // Create device
        $device = Devices::create([
            'devices_type_id' => $type->id,
            'name' => 'Device 1',
            'status' => 'active',
            'is_active' => true,
        ]);

        $playType = PlayType::create([
            'device_type_id' => $type->id,
            'name' => 'Single',
        ]);

        $playPrice = PlayPrice::create([
            'device_id' => $device->id,
            'play_type_id' => $playType->id,
            'price' => 50.0,
            'start_from' => '00:00:00',
        ]);

        $queue = [
            [
                'id' => 101,
                'type' => 'START_SESSION',
                'data' => [
                    'device_id' => $device->id,
                    'play_price_id' => $playPrice->id,
                    'duration_minutes' => null,
                    'start_time' => now()->toDateTimeString(),
                ],
            ],
        ];

        $response = $this->postJson('http://test.ps-web.test/tenant/offline/sync', [
            'queue' => $queue,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => 101,
                'status' => 'success',
            ]);

        $freshDeviceStatus = $device->fresh()->status;
        $this->assertEquals('busy', $freshDeviceStatus);
    }
}
