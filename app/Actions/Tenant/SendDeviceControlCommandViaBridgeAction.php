<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\SystemConfig;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SendDeviceControlCommandViaBridgeAction
{
    public function isBridgeOnline(): bool
    {
        $bridgeUrl = rtrim($this->bridgeUrl(), '/');
        $bridgeToken = $this->bridgeToken();

        if ($this->usesWebSocket($bridgeUrl)) {
            $response = $this->sendWebSocketMessage($bridgeUrl, 'PING_CHECK');

            return $response['ok'];
        }

        try {
            $response = Http::timeout(4)
                ->connectTimeout(2)
                ->acceptJson()
                ->post($bridgeUrl.'/iot/ping-check', [
                    'token' => $bridgeToken,
                    'ipAddress' => '127.0.0.1',
                    'deviceName' => 'BRIDGE_HEALTH',
                ]);

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 404) {
                $legacyPingResponse = Http::timeout(4)
                    ->connectTimeout(2)
                    ->acceptJson()
                    ->post($bridgeUrl.'/iot/udp-send', [
                        'token' => $bridgeToken,
                        'ipAddress' => '127.0.0.1',
                        'deviceName' => 'BRIDGE_HEALTH',
                        'ping' => true,
                    ]);

                return $legacyPingResponse->successful();
            }

            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{ok:bool,error:string|null}
     */
    public function execute(string $ipAddress, string $action, string $deviceName = 'DEVICE_CONTROL'): array
    {
        $bridgeUrl = rtrim($this->bridgeUrl(), '/');
        $bridgeToken = $this->bridgeToken();

        if ($bridgeUrl === '') {
            return [
                'ok' => false,
                'error' => 'Bridge URL غير مضبوط.',
            ];
        }

        try {
            $order = $this->buildBridgeOrder($action);

            if ($this->usesWebSocket($bridgeUrl)) {
                return $this->sendOrderViaWebSocket(
                    bridgeUrl: $bridgeUrl,
                    ipAddress: $ipAddress,
                    order: $order,
                    deviceName: $deviceName,
                );
            }

            $preflightResult = $this->verifyBridgeAndDeviceOnline(
                bridgeUrl: $bridgeUrl,
                bridgeToken: $bridgeToken,
                ipAddress: $ipAddress,
                deviceName: $deviceName,
            );

            if (! $preflightResult['ok']) {
                return $preflightResult;
            }

            $response = Http::timeout(5)
                ->connectTimeout(2)
                ->acceptJson()
                ->post($bridgeUrl.'/iot/udp-send', [
                    'token' => $bridgeToken,
                    'ipAddress' => $ipAddress,
                    'order' => $order,
                    'action' => $order,
                ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'error' => 'Bridge HTTP '.$response->status().': '.$response->body(),
                ];
            }

            $responsePayload = $response->json();
            if (! is_array($responsePayload)) {
                return [
                    'ok' => false,
                    'error' => 'Bridge أعاد استجابة غير صالحة.',
                ];
            }

            $ok = (bool) ($responsePayload['ok'] ?? false);
            if (! $ok) {
                return [
                    'ok' => false,
                    'error' => (string) ($responsePayload['error'] ?? 'فشل إرسال الأمر إلى Bridge.'),
                ];
            }

            if ($this->isExplicitlyOffline($responsePayload)) {
                return [
                    'ok' => false,
                    'error' => 'الجهاز غير متصل وقت تنفيذ أمر التحكم.',
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

    /**
     * @return array{ok:bool,error:string|null}
     */
    private function verifyBridgeAndDeviceOnline(string $bridgeUrl, string $bridgeToken, string $ipAddress, string $deviceName): array
    {
        $preflightResponse = Http::timeout(4)
            ->connectTimeout(2)
            ->acceptJson()
            ->post($bridgeUrl.'/iot/ping-check', [
                'token' => $bridgeToken,
                'ipAddress' => $ipAddress,
                'deviceName' => $deviceName,
            ]);

        if ($preflightResponse->successful()) {
            return $this->validatePreflightPayload($preflightResponse);
        }

        // Backward compatibility with bridges that do not support /iot/ping-check.
        if ($preflightResponse->status() === 404) {
            $legacyPingResponse = Http::timeout(4)
                ->connectTimeout(2)
                ->acceptJson()
                ->post($bridgeUrl.'/iot/udp-send', [
                    'token' => $bridgeToken,
                    'ipAddress' => $ipAddress,
                    'deviceName' => $deviceName,
                    'ping' => true,
                ]);

            if (! $legacyPingResponse->successful()) {
                return [
                    'ok' => false,
                    'error' => 'Bridge غير متاح: HTTP '.$legacyPingResponse->status().'.',
                ];
            }

            return $this->validatePreflightPayload($legacyPingResponse);
        }

        return [
            'ok' => false,
            'error' => 'Bridge غير متاح: HTTP '.$preflightResponse->status().'.',
        ];
    }

    /**
     * @return array{ok:bool,error:string|null}
     */
    private function validatePreflightPayload(Response $response): array
    {
        $payload = $response->json();
        if (! is_array($payload)) {
            return [
                'ok' => false,
                'error' => 'Bridge أعاد استجابة preflight غير صالحة.',
            ];
        }

        $ok = (bool) ($payload['ok'] ?? false);
        if (! $ok) {
            return [
                'ok' => false,
                'error' => (string) ($payload['error'] ?? 'Bridge لم يرجع حالة نجاح.'),
            ];
        }

        if ($this->isExplicitlyOffline($payload)) {
            return [
                'ok' => false,
                'error' => 'جهاز التحكم غير Online حالياً.',
            ];
        }

        return [
            'ok' => true,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isExplicitlyOffline(array $payload): bool
    {
        $status = strtolower((string) ($payload['status'] ?? ''));
        if (in_array($status, ['offline', 'disconnected', 'failed', 'error'], true)) {
            return true;
        }

        foreach (['deviceOnline', 'device_online', 'online', 'isOnline', 'is_online', 'reachable'] as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] === false) {
                return true;
            }
        }

        return false;
    }

    private function buildBridgeOrder(string $action): string
    {
        $normalizedAction = strtoupper(trim($action));

        if ($normalizedAction === 'OFF') {
            return 'OFF';
        }

        if (str_starts_with($normalizedAction, 'ON,')) {
            return $this->normalizeExplicitOnAction($action);
        }

        $duration = (int) SystemConfig::get('sys_close_duration', 0);

        if ($duration > 0) {
            return 'ON,Limit,'.(string) $duration;
        }

        return 'ON,Unlimited,0';
    }

    private function normalizeExplicitOnAction(string $action): string
    {
        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $action));
        if (count($parts) !== 3) {
            throw new RuntimeException('صيغة أمر التحكم ON غير صالحة.');
        }

        if (strtoupper($parts[0]) !== 'ON') {
            throw new RuntimeException('صيغة أمر التحكم ON غير صالحة.');
        }

        $mode = strtoupper($parts[1]);
        $value = (int) $parts[2];

        if ($mode === 'LIMIT') {
            if ($value <= 0) {
                throw new RuntimeException('قيمة وقت LIMIT يجب أن تكون أكبر من صفر.');
            }

            return 'ON,Limit,'.(string) $value;
        }

        if ($mode === 'UNLIMITED') {
            return 'ON,Unlimited,0';
        }

        if ($mode === 'ADD') {
            if ($value <= 0) {
                throw new RuntimeException('قيمة وقت ADD يجب أن تكون أكبر من صفر.');
            }

            return 'ON,Add,'.(string) $value;
        }

        throw new RuntimeException('نوع أمر التحكم ON غير مدعوم.');
    }

    private function bridgeUrl(): string
    {
        return trim((string) Config::get('services.bridge.url', ''));
    }

    private function bridgeToken(): string
    {
        return trim((string) Config::get('services.bridge.token', ''));
    }

    private function usesWebSocket(string $bridgeUrl): bool
    {
        return str_starts_with($bridgeUrl, 'ws://') || str_starts_with($bridgeUrl, 'wss://');
    }

    /**
     * @return array{ok:bool,error:string|null}
     */
    private function sendOrderViaWebSocket(string $bridgeUrl, string $ipAddress, string $order, string $deviceName = 'DEVICE_CONTROL'): array
    {
        $message = $ipAddress.','.$order.','.$deviceName;
        $result = $this->sendWebSocketMessage($bridgeUrl, $message);

        if (! $result['ok']) {
            return [
                'ok' => false,
                'error' => $result['error'] ?? 'فشل الاتصال بالـ Bridge عبر WebSocket.',
            ];
        }

        return [
            'ok' => true,
            'error' => null,
        ];
    }

    /**
     * @return array{ok:bool,error:string|null}
     */
    private function sendWebSocketMessage(string $bridgeUrl, string $message): array
    {
        $parts = parse_url($bridgeUrl);
        if (! is_array($parts)) {
            return [
                'ok' => false,
                'error' => 'Bridge URL غير صالح.',
            ];
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        $port = (int) ($parts['port'] ?? ($scheme === 'wss' ? 443 : 80));
        $path = (string) ($parts['path'] ?? '/');
        if ($path === '') {
            $path = '/';
        }
        $query = (string) ($parts['query'] ?? '');
        if ($query !== '') {
            $path .= '?'.$query;
        }

        if ($host === '' || ! in_array($scheme, ['ws', 'wss'], true)) {
            return [
                'ok' => false,
                'error' => 'Bridge URL يجب أن يكون ws:// أو wss:// صالح.',
            ];
        }

        $transport = $scheme === 'wss' ? 'tls' : 'tcp';
        $socket = @stream_socket_client(
            $transport.'://'.$host.':'.$port,
            $errorNumber,
            $errorMessage,
            4
        );

        if (! is_resource($socket)) {
            return [
                'ok' => false,
                'error' => 'فشل الاتصال بالـ Bridge: '.$errorMessage,
            ];
        }

        stream_set_timeout($socket, 5);

        $key = base64_encode(random_bytes(16));
        $headers = "GET {$path} HTTP/1.1\r\n"
            ."Host: {$host}:{$port}\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($socket, $headers);

        $handshakeResponse = '';
        while (! feof($socket)) {
            $line = fgets($socket);
            if ($line === false) {
                break;
            }

            $handshakeResponse .= $line;
            if (rtrim($line) === '') {
                break;
            }
        }

        if (! str_contains($handshakeResponse, ' 101 ')) {
            fclose($socket);

            return [
                'ok' => false,
                'error' => 'Handshake WebSocket فشل.',
            ];
        }

        fwrite($socket, $this->encodeWebSocketFrame($message));

        $reply = $this->decodeWebSocketFrame($socket);
        fclose($socket);

        if ($reply === null) {
            return [
                'ok' => false,
                'error' => 'لم يصل رد من الـ Bridge عبر WebSocket.',
            ];
        }

        $payload = json_decode($reply, true);
        if (is_array($payload)) {
            $status = strtolower((string) ($payload['status'] ?? ''));
            $ok = (bool) ($payload['ok'] ?? false);

            if ($ok || in_array($status, ['online', 'success'], true)) {
                return ['ok' => true, 'error' => null];
            }

            return [
                'ok' => false,
                'error' => (string) ($payload['error'] ?? $payload['message'] ?? 'Bridge رجع خطأ في WebSocket.'),
            ];
        }

        if (str_contains(strtolower($reply), 'online') || str_contains(strtolower($reply), 'success')) {
            return ['ok' => true, 'error' => null];
        }

        return [
            'ok' => false,
            'error' => 'Bridge رد بنص غير مفهوم: '.$reply,
        ];
    }

    private function encodeWebSocketFrame(string $payload): string
    {
        $length = strlen($payload);
        $frame = chr(0x81);

        $mask = random_bytes(4);

        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0x80 | 126).pack('n', $length);
        } else {
            $frame .= chr(0x80 | 127).pack('J', $length);
        }

        $frame .= $mask;

        $maskedPayload = '';
        for ($index = 0; $index < $length; $index++) {
            $maskedPayload .= $payload[$index] ^ $mask[$index % 4];
        }

        return $frame.$maskedPayload;
    }

    private function decodeWebSocketFrame($socket): ?string
    {
        $header = fread($socket, 2);
        if ($header === false || strlen($header) < 2) {
            return null;
        }

        $firstByte = ord($header[0]);
        $secondByte = ord($header[1]);

        $opcode = $firstByte & 0x0F;
        if ($opcode === 0x08) {
            return null;
        }

        $isMasked = ($secondByte & 0x80) === 0x80;
        $length = $secondByte & 0x7F;

        if ($length === 126) {
            $extended = fread($socket, 2);
            if ($extended === false || strlen($extended) < 2) {
                return null;
            }

            $length = unpack('n', $extended)[1];
        } elseif ($length === 127) {
            $extended = fread($socket, 8);
            if ($extended === false || strlen($extended) < 8) {
                return null;
            }

            $length = (int) unpack('J', $extended)[1];
        }

        $mask = '';
        if ($isMasked) {
            $mask = fread($socket, 4);
            if ($mask === false || strlen($mask) < 4) {
                return null;
            }
        }

        $payload = '';
        while (strlen($payload) < $length) {
            $chunk = fread($socket, $length - strlen($payload));
            if ($chunk === false || $chunk === '') {
                break;
            }

            $payload .= $chunk;
        }

        if (strlen($payload) !== $length) {
            return null;
        }

        if ($isMasked) {
            $unmasked = '';
            for ($index = 0; $index < $length; $index++) {
                $unmasked .= $payload[$index] ^ $mask[$index % 4];
            }

            return $unmasked;
        }

        return $payload;
    }
}
