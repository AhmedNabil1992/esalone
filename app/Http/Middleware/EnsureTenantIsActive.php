<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (! $tenant || ($tenant->is_active && $tenant->provisioning_status !== 'suspended')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'error_code' => 'TENANT_SUSPENDED',
                'message' => 'Tenant is suspended.',
                'errors' => [],
            ], 423)->withHeaders($this->noCacheHeaders());
        }

        return response()->view('errors.tenant-suspended', [
            'centralUrl' => rtrim((string) config('app.url'), '/'),
            'requestedHost' => $request->getHost(),
        ], 423)->withHeaders($this->noCacheHeaders());
    }

    /** @return array<string, string> */
    private function noCacheHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private',
            'X-Robots-Tag' => 'noindex, nofollow',
        ];
    }
}
