<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Jobs\DeleteTenantJob;
use App\Models\Tenant;
use App\Models\TenantProvisioningRequest;
use App\Services\TenantWebhookService;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantDeletionController extends Controller
{
    public function __invoke(string $tenant, TenantLifecycleLock $lock, TenantWebhookService $webhooks): JsonResponse
    {
        $model = Tenant::query()->find($tenant);
        if (! $model) {
            return response()->json([
                'error_code' => 'TENANT_NOT_FOUND',
                'message' => 'Tenant not found.',
                'errors' => [],
            ], 404);
        }

        try {
            $shouldDispatch = $lock->run($tenant, function () use ($model, $webhooks): bool {
                $current = $model->fresh();
                if (! $current || $current->deletion_started_at !== null) {
                    return false;
                }

                $current->update([
                    'provisioning_status' => 'deleting',
                    'deletion_started_at' => now(),
                    'is_active' => false,
                ]);
                TenantProvisioningRequest::query()
                    ->where('tenant_id', $current->getTenantKey())
                    ->update(['status' => 'deleting']);

                if ($current->external_subscription_id) {
                    $webhooks->dispatch('tenant.deletion_started', $current->external_subscription_id, [
                        'provisioning_request_id' => $current->provisioning_request_id,
                        'user_id' => $current->user_id,
                        'domain' => $current->domains()->value('domain'),
                    ], $current);
                }

                return true;
            });
        } catch (LockTimeoutException) {
            return response()->json([
                'error_code' => 'TENANT_BUSY',
                'message' => 'Tenant is busy. Retry the deletion shortly.',
                'errors' => [],
            ], 409);
        }

        if ($shouldDispatch) {
            DeleteTenantJob::dispatch($tenant);
        }

        return response()->json([
            'message' => 'Tenant deletion has started.',
            'tenant_id' => $tenant,
            'external_subscription_id' => $model->external_subscription_id,
            'status' => 'deleting',
        ], 202);
    }
}
