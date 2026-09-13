<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RenewTenantRequest;
use App\Models\Tenant;
use App\Tenancy\TenantLifecycleLock;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

class TenantRenewalController extends Controller
{
    public function __invoke(RenewTenantRequest $request, Tenant $tenant, TenantLifecycleLock $lock): JsonResponse
    {
        try {
            return $lock->run((string) $tenant->getTenantKey(), function () use ($request, $tenant): JsonResponse {
                $tenant = $tenant->fresh();
                abort_if(! $tenant, 404, 'Tenant not found.');
                abort_if($tenant->getAttribute('deletion_started_at') !== null, 410, 'Tenant deletion has started.');
                abort_if($tenant->getAttribute('provisioning_status') === 'provisioning', 409, 'Tenant is still being provisioned.');
                abort_if($tenant->ended_at && now()->greaterThanOrEqualTo(
                    $tenant->ended_at->copy()->addDays((int) config('tenancy.expiration_grace_days', 3))
                ), 410, 'The renewal grace period has ended.');

                $endedAt = Carbon::parse($request->validated('ended_at'))->utc();
                if ($tenant->ended_at && $endedAt->isBefore($tenant->ended_at)) {
                    throw ValidationException::withMessages(['ended_at' => 'The renewal cannot shorten the current subscription.']);
                }

                $tenant->update(['ended_at' => $endedAt, 'is_active' => true]);

                return response()->json([
                    'tenant_id' => $tenant->getTenantKey(),
                    'ended_at' => $tenant->ended_at->toIso8601String(),
                    'is_active' => $tenant->is_active,
                ]);
            });
        } catch (LockTimeoutException) {
            return response()->json(['message' => 'Tenant is busy. Retry the renewal shortly.'], 409);
        }
    }
}
