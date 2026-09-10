<?php

declare(strict_types=1);

namespace App\Tenancy;

use Closure;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class TenantLifecycleLock
{
    public function run(string $tenantId, Closure $callback): mixed
    {
        if (tenancy()->initialized) {
            throw new RuntimeException('Tenant lifecycle changes must run in the central context.');
        }

        return Cache::store()->lock('tenant-lifecycle:'.$tenantId, 600)->block(5, $callback);
    }
}
