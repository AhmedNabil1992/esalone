<?php

declare(strict_types=1);

namespace App\Support\Mqtt;

use PhpMqtt\Client\Contracts\MqttClient as MqttClientContract;
use PhpMqtt\Client\MqttClient;

class MqttClientFactory
{
    public function make(string $host, int $port, string $clientId): MqttClientContract
    {
        return new MqttClient($host, $port, $clientId, MqttClient::MQTT_3_1_1);
    }
}
