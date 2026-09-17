<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Devices;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Livewire\Livewire;
use RuntimeException;

class DispatchDeviceControlCommandAction
{
    /**
     * @return array{ok:bool,error:string|null}
     */
    public function execute(Devices $device, User $actor, string $action, string $description = ''): array
    {
        if (! $this->isControlActive()) {
            return [
                'ok' => true,
                'error' => null,
            ];
        }

        $ipAddress = trim((string) ($device->ip_address ?? ''));
        if ($ipAddress === '127.0.0.1') {
            return [
                'ok' => true,
                'error' => null,
            ];
        }

        if ($ipAddress === '') {
            throw new RuntimeException('IP جهاز التحكم غير صالح.');
        }

        if (! $device->is_time) {
            return [
                'ok' => true,
                'error' => null,
            ];
        }

        $normalizedAction = $this->normalizeAction($action);
        if ($normalizedAction === null) {
            throw new RuntimeException('أمر التحكم غير مدعوم.');
        }

        $deviceName = trim((string) ($device->name ?? 'DEVICE_CONTROL'));

        if (class_exists(Livewire::class)) {
            Livewire::dispatch('send-bridge-device-command', [
                'ip' => $ipAddress,
                'command' => $normalizedAction,
                'deviceName' => $deviceName,
            ]);
        }

        $result = app(SendDeviceControlCommandViaBridgeAction::class)->execute(
            ipAddress: $ipAddress,
            action: $normalizedAction,
            deviceName: $deviceName,
        );

        if (! $result['ok']) {
            $message = trim((string) ($result['error'] ?? 'فشل تنفيذ أمر التحكم.'));
            $context = trim($description) !== '' ? ' ('.$description.')' : '';

            throw new RuntimeException($message.$context);
        }

        return [
            'ok' => true,
            'error' => null,
        ];
    }

    private function isControlActive(): bool
    {
        return in_array((string) SystemConfig::get('sys_control_active', '0'), ['1', 'true', 'on', 'yes'], true);
    }

    private function normalizeAction(string $action): ?string
    {
        $candidate = trim($action);
        if ($candidate === '') {
            return null;
        }

        $upper = strtoupper($candidate);
        if (in_array($upper, ['ON', 'OFF'], true)) {
            return $upper;
        }

        if (preg_match('/^ON\s*,\s*(LIMIT|UNLIMITED|ADD)\s*,\s*\d+$/i', $candidate) !== 1) {
            return null;
        }

        $parts = array_map(static fn (string $part): string => trim($part), explode(',', $candidate));
        $mode = strtoupper($parts[1]);
        $value = (int) $parts[2];

        return match ($mode) {
            'LIMIT' => $value > 0 ? 'ON,Limit,'.(string) $value : null,
            'UNLIMITED' => 'ON,Unlimited,0',
            'ADD' => $value > 0 ? 'ON,Add,'.(string) $value : null,
            default => null,
        };
    }
}
