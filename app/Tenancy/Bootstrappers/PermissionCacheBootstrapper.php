<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class PermissionCacheBootstrapper implements TenancyBootstrapper
{
    private string $originalKey;

    public function bootstrap(Tenant $tenant): void
    {
        $this->originalKey = (string) config('permission.cache.key');
        $this->switchKey($this->originalKey.'.tenant.'.$tenant->getTenantKey());
    }

    public function revert(): void
    {
        $this->switchKey($this->originalKey);
    }

    private function switchKey(string $key): void
    {
        config()->set('permission.cache.key', $key);
        $registrar = app(PermissionRegistrar::class);
        $registrar->clearPermissionsCollection();
        $registrar->initializeCache();
    }
}
