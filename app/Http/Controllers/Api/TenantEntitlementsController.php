<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UpdateTenantEntitlementsRequest;
use App\Models\Tenant;
use App\Services\TenantWebhookService;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantEntitlementsController extends Controller
{
    public function __invoke(
        UpdateTenantEntitlementsRequest $request,
        string $tenant,
        TenantLifecycleLock $lock,
        TenantWebhookService $webhooks,
    ): JsonResponse {
        $model = Tenant::query()->find($tenant);
        if (! $model) {
            return $this->error('TENANT_NOT_FOUND', 'Tenant not found.', 404);
        }

        try {
            return $lock->run($tenant, function () use ($request, $model, $webhooks): JsonResponse {
                $current = $model->fresh();
                if (! $current) {
                    return $this->error('TENANT_NOT_FOUND', 'Tenant not found.', 404);
                }
                if ($current->deletion_started_at !== null) {
                    return $this->error('TENANT_DELETION_STARTED', 'Tenant deletion has started.', 410);
                }
                if ($current->provisioning_status === 'provisioning') {
                    return $this->error('TENANT_BUSY', 'Tenant is still being provisioned.', 409);
                }

                $current->update($request->validated());

                if ($current->external_subscription_id) {
                    $webhooks->dispatch('tenant.entitlements_updated', $current->external_subscription_id, [
                        'provisioning_request_id' => $current->provisioning_request_id,
                        'user_id' => $current->user_id,
                        'domain' => $current->domains()->value('domain'),
                        'branches_limit' => $current->branches_limit,
                        'has_ai_subscription' => $current->has_ai_subscription,
                        'ai_requests_limit' => $current->ai_requests_limit,
                    ], $current);
                }

                return response()->json([
                    'tenant_id' => $current->getTenantKey(),
                    'external_subscription_id' => $current->external_subscription_id,
                    'branches_limit' => $current->branches_limit,
                    'has_ai_subscription' => $current->has_ai_subscription,
                    'ai_requests_limit' => $current->ai_requests_limit,
                ]);
            });
        } catch (LockTimeoutException) {
            return $this->error('TENANT_BUSY', 'Tenant is busy. Retry the entitlement update shortly.', 409);
        }
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message, 'errors' => []], $status);
    }
}
