<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use RuntimeException;
use Stancl\Tenancy\Features\UserImpersonation;
use Throwable;

class TenantImpersonationController extends Controller
{
    public function __invoke(Tenant $tenant): JsonResponse
    {
        abort_if($tenant->getAttribute('deletion_started_at') !== null, 410, 'Tenant deletion has started.');
        abort_unless($tenant->getAttribute('provisioning_status') === 'ready', 409, 'Tenant is not ready.');
        abort_if($tenant->ended_at && now()->greaterThanOrEqualTo(
            $tenant->ended_at->copy()->addDays((int) config('tenancy.expiration_grace_days', 3))
        ), 410, 'The tenant grace period has ended.');

        $domain = $tenant->domains()->orderBy('id')->value('domain');
        abort_if(blank($domain), 409, 'Tenant has no domain.');

        try {
            tenancy()->initialize($tenant);
            $ownerId = User::query()
                ->where('email', '!=', 'admin@etech-valley.com')
                ->where('email', 'not like', 'disabled-support-%@invalid.local')
                ->oldest('id')
                ->value('id');
        } catch (Throwable $throwable) {
            report($throwable);

            throw new RuntimeException('Unable to resolve the tenant owner.', previous: $throwable);
        } finally {
            tenancy()->end();
        }

        abort_if(! $ownerId, 409, 'Tenant owner was not found.');

        $token = tenancy()->impersonate($tenant, (string) $ownerId, '/admin', 'web');
        $url = tenant_route((string) $domain, 'tenant.impersonate', ['token' => $token->token]);

        return response()->json([
            'tenant_id' => $tenant->getTenantKey(),
            'token' => $token->token,
            'impersonation_url' => $url,
            'expires_in' => UserImpersonation::$ttl,
        ])->header('Cache-Control', 'no-store, private');
    }
}
