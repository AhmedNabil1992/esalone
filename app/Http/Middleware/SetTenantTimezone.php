<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenant\TenantClock;
use Closure;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Http\Request;

class SetTenantTimezone
{
    public function handle(Request $request, Closure $next): mixed
    {
        FilamentTimezone::set(app(TenantClock::class)->timezone());

        return $next($request);
    }
}
