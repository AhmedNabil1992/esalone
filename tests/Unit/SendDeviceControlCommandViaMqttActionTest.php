<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenant\SendDeviceControlCommandViaMqttAction;
use App\Models\Tenant\Devices;
use App\Support\Mqtt\MqttClientFactory;
use Illuminate\Support\Facades\Config;
use Mockery;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use Tests\TestCase;

class SendDeviceControlCommandViaMqttActionTest extends TestCase
{
    public function test_it_publishes_command_to_device_mac_topic_with_qos_one(): void
    {
        Config::set('services.mqtt.host', 'mqtt.example.test');
        Config::set('services.mqtt.port', 1883);
        Config::set('services.mqtt.username', 'user');
        Config::set('services.mqtt.password', 'secret');
        Config::set('services.mqtt.topic_prefix', 'ps-web');

        $client = Mockery::mock(MqttClientContract::class);
        $client->shouldReceive('connect')->once()->with(Mockery::type(ConnectionSettings::class), true);
        $client->shouldReceive('publish')->once()->with('ps-web/devices/aabbccddeeff/commands', 'ON,Limit,60', 1, false);
        $client->shouldReceive('loop')->once()->with(true, true, 3);
        $client->shouldReceive('disconnect')->once();

        $factory = Mockery::mock(MqttClientFactory::class);
        $factory->shouldReceive('make')
            ->once()
            ->with('mqtt.example.test', 1883, Mockery::pattern('/^ps-web-aabbccddeeff-/'))
            ->andReturn($client);

        $device = new Devices(['mac_address' => 'AA:BB:CC:DD:EE:FF']);
        $result = (new SendDeviceControlCommandViaMqttAction($factory))->execute($device, 'ON,Limit,60');

        $this->assertTrue($result['ok'], (string) $result['error']);
        $this->assertNull($result['error']);
    }

    public function test_it_rejects_a_device_without_a_valid_mac_address(): void
    {
        $factory = Mockery::mock(MqttClientFactory::class);
        $factory->shouldNotReceive('make');

        $device = new Devices(['mac_address' => null]);
        $result = (new SendDeviceControlCommandViaMqttAction($factory))->execute($device, 'OFF');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('MAC', (string) $result['error']);
    }
}
