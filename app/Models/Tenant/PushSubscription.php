<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use NotificationChannels\WebPush\PushSubscription as BasePushSubscription;

class PushSubscription extends BasePushSubscription
{
    protected $connection = 'tenant';
}
