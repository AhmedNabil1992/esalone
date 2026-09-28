<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantProvisioningTokenIsValid
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configuredTokens = array_values(array_unique(array_filter(array_merge(
            (array) config('services.tenant_provisioning.tokens', []),
            [(string) config('services.tenant_provisioning.token', '')]
        ))));
        $providedToken = (string) $request->bearerToken();

        if ($configuredTokens === []) {
            return new JsonResponse([
                'message' => 'Tenant provisioning API token is not configured.',
                'error_code' => 'UNAUTHORIZED',
            ], 503);
        }

        $isValid = $providedToken !== '' && collect($configuredTokens)
            ->contains(fn (mixed $token): bool => is_string($token) && hash_equals($token, $providedToken));

        if (! $isValid) {
            return new JsonResponse([
                'message' => 'Unauthorized.',
                'error_code' => 'UNAUTHORIZED',
            ], 401);
        }

        return $next($request);
    }
}
