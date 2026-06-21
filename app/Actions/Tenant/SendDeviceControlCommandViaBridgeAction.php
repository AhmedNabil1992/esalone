<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\DeviceControlCommand;
use App\Models\Tenant\SystemConfig;
use Illuminate\Support\Facades\Http;

class SendDeviceControlCommandViaBridgeAction
{
    /**
     * @return array{ok:bool,error:string|null}
     */
    public function execute(DeviceControlCommand $command): array
    {
        $bridgeUrl = rtrim((string) SystemConfig::get('sys_iot_bridge_url', 'http://127.0.0.1:17325'), '/');
        $bridgeToken = (string) SystemConfig::get('sys_iot_bridge_token', 'change-me');
        $udpPort = (int) SystemConfig::get('sys_iot_udp_port', 4210);

        if ($bridgeUrl === '') {
            return [
                'ok' => false,
                'error' => 'Bridge URL غير مضبوط.',
            ];
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->post($bridgeUrl.'/iot/udp-send', [
                    'token' => $bridgeToken,
                    'ipAddress' => (string) $command->ip_address,
                    'deviceName' => (string) $command->device_name,
                    'commandName' => (string) $command->command,
                    'udpPort' => $udpPort > 0 ? $udpPort : 4210,
                    'ping' => true,
                ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'error' => 'Bridge HTTP '.$response->status().': '.$response->body(),
                ];
            }

            $payload = $response->json();
            $ok = (bool) ($payload['ok'] ?? false);
            if (! $ok) {
                return [
                    'ok' => false,
                    'error' => (string) ($payload['error'] ?? 'فشل إرسال الأمر إلى Bridge.'),
                ];
            }

            return [
                'ok' => true,
                'error' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
