<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Contracts\TenantCouldNotBeIdentifiedException;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;
use Stancl\Tenancy\Tenancy;
use Symfony\Component\HttpFoundation\Response;

class InitializeTenancyByDomainIfExists
{
    public function __construct(
        protected Tenancy $tenancy,
        protected DomainTenantResolver $resolver,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->routeAlreadyHandlesTenancy($request)) {
            return $next($request);
        }

        if (! $this->tenancy->initialized) {
            try {
                $tenant = $this->resolver->resolve($request->getHost());
                $this->tenancy->initialize($tenant);
            } catch (TenantCouldNotBeIdentifiedException) {
                // Keep central context when host is not a tenant domain.
            }
        }

        return $next($request);
    }

    protected function routeAlreadyHandlesTenancy(Request $request): bool
    {
        $route = $request->route();

        if (! $route) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if (str_starts_with($middleware, 'Stancl\\Tenancy\\Middleware\\InitializeTenancyBy')) {
                return true;
            }
        }

        return false;
    }
}
