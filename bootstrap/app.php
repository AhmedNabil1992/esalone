<?php

use App\Http\Middleware\CheckTenantBranchLimit;
use App\Http\Middleware\CheckTenantSubscriptionExpiry;
use App\Http\Middleware\EnforceApiIdempotency;
use App\Http\Middleware\EnsureTenantProvisioningTokenIsValid;
use App\Http\Middleware\InitializeTenancyByDomainIfExists;
use App\Http\Middleware\InjectProductionSecurityGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(InjectProductionSecurityGuard::class);

        $middleware->web(prepend: [
            InitializeTenancyByDomainIfExists::class,
        ]);

        $middleware->web(append: [
            CheckTenantSubscriptionExpiry::class,
        ]);

        $middleware->alias([
            'tenant.provisioning.token' => EnsureTenantProvisioningTokenIsValid::class,
            'api.idempotency' => EnforceApiIdempotency::class,
            'tenant.branch.limit' => CheckTenantBranchLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReport(TenantCouldNotBeIdentifiedOnDomainException::class);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        $exceptions->render(function (TenantCouldNotBeIdentifiedOnDomainException $exception, Request $request) {
            $host = (string) $request->getHost();
            $centralDomains = array_values((array) config('tenancy.central_domains', []));

            $requestedName = null;

            foreach ($centralDomains as $centralDomain) {
                if (! is_string($centralDomain) || $centralDomain === '') {
                    continue;
                }

                $suffix = '.'.$centralDomain;
                if (str_ends_with($host, $suffix)) {
                    $requestedName = substr($host, 0, -strlen($suffix));
                    break;
                }
            }

            if (! is_string($requestedName) || $requestedName === '' || str_contains($requestedName, '.')) {
                $requestedName = null;
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Tenant domain is not registered.',
                    'requested_name' => $requestedName,
                    'requested_host' => $host,
                ], 404);
            }

            return response()->view('errors.tenant-not-found', [
                'requestedName' => $requestedName,
                'requestedHost' => $host,
                'centralDomain' => $centralDomains[0] ?? null,
            ], 404);
        });
    })->create();
