<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UpdateTenantEntitlementsRequest;
use App\Models\Tenant;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantEntitlementsController extends Controller
{
    public function __invoke(
        UpdateTenantEntitlementsRequest $request,
        Tenant $tenant,
        TenantLifecycleLock $lock,
    ): JsonResponse {
        try {
            return $lock->run((string) $tenant->getTenantKey(), function () use ($request, $tenant): JsonResponse {
                $tenant = $tenant->fresh();
                abort_if(! $tenant, 404, 'Tenant not found.');
                abort_if($tenant->getAttribute('deletion_started_at') !== null, 410, 'Tenant deletion has started.');
                abort_if($tenant->getAttribute('provisioning_status') === 'provisioning', 409, 'Tenant is still being provisioned.');

                $tenant->update($request->validated());

                return response()->json([
                    'tenant_id' => $tenant->getTenantKey(),
                    'branches_limit' => $tenant->branches_limit,
                    'has_ai_subscription' => $tenant->has_ai_subscription,
                    'ai_requests_limit' => $tenant->ai_requests_limit,
                ]);
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Tenant is busy. Retry the entitlement update shortly.'], 409);
        }
    }
}
