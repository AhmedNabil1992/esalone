<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\DeviceControlCommand;
use App\Models\Tenant\Devices;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use Illuminate\Support\Carbon;

class QueueDeviceControlCommandAction
{
    public function execute(Devices $device, User $actor, string $action, string $description): ?DeviceControlCommand
    {
        if (! $this->isControlActive()) {
            return null;
        }

        $ipAddress = trim((string) ($device->ip_address ?? ''));
        if ($ipAddress === '' || $ipAddress === '127.0.0.1') {
            return null;
        }

        if (! $device->is_time) {
            return null;
        }

        $normalizedAction = strtoupper(trim($action));
        $command = match ($normalizedAction) {
            'ON' => 11,
            'OFF' => 10,
            default => null,
        };

        if ($command === null) {
            return null;
        }

        $queuedCommand = DeviceControlCommand::query()->create([
            'device_id' => $device->id,
            'ip_address' => $ipAddress,
            'device_name' => (string) $device->name,
            'command' => $command,
            'action' => $normalizedAction,
            'username' => $actor->name,
            'remark' => $description,
            'status' => 'pending',
            'processed_by' => null,
            'retry_count' => 0,
        ]);

        $sendResult = app(SendDeviceControlCommandViaBridgeAction::class)->execute($queuedCommand);

        if ($sendResult['ok']) {
            $queuedCommand->update([
                'status' => 'sent',
                'processed_by' => 'bridge',
                'sent_at' => Carbon::now(),
                'error_message' => null,
            ]);
        } else {
            $queuedCommand->update([
                'status' => 'pending',
                'retry_count' => (int) $queuedCommand->retry_count + 1,
                'error_message' => $sendResult['error'],
            ]);
        }

        return $queuedCommand->fresh();
    }

    private function isControlActive(): bool
    {
        return in_array((string) SystemConfig::get('sys_control_active', '0'), ['1', 'true', 'on', 'yes'], true);
    }
}
