<?php

namespace Tests\Unit;

use App\Actions\Tenant\SendDeviceControlCommandViaBridgeAction;
use App\Models\Tenant\SystemConfig;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SendDeviceControlCommandViaBridgeActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('system_configs')) {
            Schema::drop('system_configs');
        }

        Schema::create('system_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('system_configs')) {
            Schema::drop('system_configs');
        }

        parent::tearDown();
    }

    public function test_execute_sends_order_payload_after_online_preflight(): void
    {
        Config::set('services.bridge.url', 'http://127.0.0.1:17325');
        Config::set('services.bridge.token', 'bridge-token');
        SystemConfig::set('sys_close_duration', '30', 'system');

        Http::fake([
            'http://127.0.0.1:17325/iot/ping-check' => Http::response([
                'ok' => true,
                'status' => 'online',
                'deviceOnline' => true,
            ], 200),
            'http://127.0.0.1:17325/iot/udp-send' => function (Request $request) {
                $this->assertSame('192.168.1.209', $request['ipAddress']);
                $this->assertSame('ON,Limit,30', $request['order']);
                $this->assertSame('ON,Limit,30', $request['action']);

                return Http::response([
                    'ok' => true,
                    'status' => 'success',
                ], 200);
            },
        ]);

        $result = app(SendDeviceControlCommandViaBridgeAction::class)->execute(
            ipAddress: '192.168.1.209',
            action: 'ON',
        );

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);

        Http::assertSentCount(2);
    }

    public function test_execute_returns_error_when_preflight_reports_device_offline(): void
    {
        Config::set('services.bridge.url', 'http://127.0.0.1:17325');
        Config::set('services.bridge.token', 'bridge-token');

        Http::fake([
            'http://127.0.0.1:17325/iot/ping-check' => Http::response([
                'ok' => true,
                'status' => 'offline',
                'deviceOnline' => false,
            ], 200),
            'http://127.0.0.1:17325/iot/udp-send' => Http::response([
                'ok' => true,
            ], 200),
        ]);

        $result = app(SendDeviceControlCommandViaBridgeAction::class)->execute(
            ipAddress: '192.168.1.210',
            action: 'OFF',
        );

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('غير Online', (string) $result['error']);

        Http::assertSent(function (Request $request): bool {
            return str_contains($request->url(), '/iot/ping-check');
        });

        Http::assertNotSent(function (Request $request): bool {
            return str_contains($request->url(), '/iot/udp-send') && ! ((bool) ($request['ping'] ?? false));
        });
    }

    public function test_execute_accepts_explicit_on_add_action_payload(): void
    {
        Config::set('services.bridge.url', 'http://127.0.0.1:17325');
        Config::set('services.bridge.token', 'bridge-token');

        Http::fake([
            'http://127.0.0.1:17325/iot/ping-check' => Http::response([
                'ok' => true,
                'status' => 'online',
                'deviceOnline' => true,
            ], 200),
            'http://127.0.0.1:17325/iot/udp-send' => function (Request $request) {
                $this->assertSame('ON,Add,15', $request['order']);
                $this->assertSame('ON,Add,15', $request['action']);

                return Http::response([
                    'ok' => true,
                    'status' => 'success',
                ], 200);
            },
        ]);

        $result = app(SendDeviceControlCommandViaBridgeAction::class)->execute(
            ipAddress: '192.168.1.209',
            action: 'ON,Add,15',
        );

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
    }

    public function test_execute_accepts_explicit_on_unlimited_action_payload(): void
    {
        Config::set('services.bridge.url', 'http://127.0.0.1:17325');
        Config::set('services.bridge.token', 'bridge-token');

        Http::fake([
            'http://127.0.0.1:17325/iot/ping-check' => Http::response([
                'ok' => true,
                'status' => 'online',
                'deviceOnline' => true,
            ], 200),
            'http://127.0.0.1:17325/iot/udp-send' => function (Request $request) {
                $this->assertSame('ON,Unlimited,0', $request['order']);
                $this->assertSame('ON,Unlimited,0', $request['action']);

                return Http::response([
                    'ok' => true,
                    'status' => 'success',
                ], 200);
            },
        ]);

        $result = app(SendDeviceControlCommandViaBridgeAction::class)->execute(
            ipAddress: '192.168.1.209',
            action: 'ON,Unlimited,0',
        );

        $this->assertTrue($result['ok']);
        $this->assertNull($result['error']);
    }
}
