<?php

declare(strict_types=1);

namespace App\Events\Tenant;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceControlUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public string $tenantKey,
        public string $action,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('tenant.'.$this->tenantKey.'.device-control');
    }

    public function broadcastAs(): string
    {
        return 'device-control.updated';
    }

    /**
     * @return array{tenant:string,action:string,at:string}
     */
    public function broadcastWith(): array
    {
        return [
            'tenant' => $this->tenantKey,
            'action' => $this->action,
            'at' => now()->toIso8601String(),
        ];
    }
}
