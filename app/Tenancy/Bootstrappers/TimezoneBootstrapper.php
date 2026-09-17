<?php

declare(strict_types=1);

namespace App\Tenancy\Bootstrappers;

use Stancl\Tenancy\Contracts\TenancyBootstrapper;
use Stancl\Tenancy\Contracts\Tenant;

class TimezoneBootstrapper implements TenancyBootstrapper
{
    public function bootstrap(Tenant $tenant): void
    {
        // Application and PHP timezones intentionally remain UTC.
    }

    public function revert(): void
    {
        // No global timezone state is mutated during tenant bootstrap.
    }
}
