<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Devices;
use App\Support\Mqtt\MqttClientFactory;
use Illuminate\Support\Facades\Config;
use PhpMqtt\Client\ConnectionSettings;
use Throwable;

class SendDeviceControlCommandViaMqttAction
{
    public function __construct(private readonly MqttClientFactory $clientFactory) {}

    public function isBrokerOnline(): bool
    {
        $client = null;

        try {
            $client = $this->clientFactory->make($this->host(), $this->port(), $this->clientId('health'));
            $client->connect($this->connectionSettings(), true);
            $client->disconnect();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return array{ok:bool,error:string|null}
     */
    public function execute(Devices $device, string $action): array
    {
        $macAddress = $this->normalizeMacAddress((string) $device->mac_address);
        if ($macAddress === null) {
            return [
                'ok' => false,
                'error' => 'عنوان MAC غير مضبوط للجهاز، وهو مطلوب للاتصال المباشر عبر MQTT.',
            ];
        }

        $client = null;

        try {
            $client = $this->clientFactory->make($this->host(), $this->port(), $this->clientId($macAddress));
            $client->connect($this->connectionSettings(), true);
            $client->publish($this->commandTopic($macAddress), $action, 1, false);
            $client->loop(true, true, 3);
            $client->disconnect();

            return ['ok' => true, 'error' => null];
        } catch (Throwable $exception) {
            try {
                if ($client?->isConnected()) {
                    $client->disconnect();
                }
            } catch (Throwable) {
                // Preserve the original MQTT failure.
            }

            return [
                'ok' => false,
                'error' => 'فشل إرسال أمر MQTT: '.$exception->getMessage(),
            ];
        }
    }

    private function connectionSettings(): ConnectionSettings
    {
        return (new ConnectionSettings)
            ->setUsername($this->nullableConfigString('services.mqtt.username'))
            ->setPassword($this->nullableConfigString('services.mqtt.password'))
            ->setConnectTimeout((int) Config::get('services.mqtt.connect_timeout', 5))
            ->setSocketTimeout((int) Config::get('services.mqtt.socket_timeout', 5))
            ->setKeepAliveInterval(30)
            ->setUseTls((bool) Config::get('services.mqtt.tls', false))
            ->setTlsVerifyPeer((bool) Config::get('services.mqtt.tls_verify_peer', true))
            ->setTlsVerifyPeerName((bool) Config::get('services.mqtt.tls_verify_peer_name', true));
    }

    private function host(): string
    {
        return trim((string) Config::get('services.mqtt.host', '127.0.0.1'));
    }

    private function port(): int
    {
        return (int) Config::get('services.mqtt.port', 1883);
    }

    private function commandTopic(string $macAddress): string
    {
        $prefix = trim((string) Config::get('services.mqtt.topic_prefix', 'ps-web'), '/');

        return $prefix.'/devices/'.$macAddress.'/commands';
    }

    private function clientId(string $suffix): string
    {
        return 'ps-web-'.preg_replace('/[^a-zA-Z0-9_-]/', '', $suffix).'-'.bin2hex(random_bytes(4));
    }

    private function normalizeMacAddress(string $macAddress): ?string
    {
        $normalized = strtolower((string) preg_replace('/[^a-fA-F0-9]/', '', $macAddress));

        return strlen($normalized) === 12 ? $normalized : null;
    }

    private function nullableConfigString(string $key): ?string
    {
        $value = trim((string) Config::get($key, ''));

        return $value !== '' ? $value : null;
    }
}
