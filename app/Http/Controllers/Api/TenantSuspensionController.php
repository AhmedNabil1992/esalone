<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Services\TenantWebhookService;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class TenantSuspensionController extends Controller
{
    public function __invoke(
        string $tenant,
        TenantLifecycleLock $lock,
        TenantWebhookService $webhooks,
    ): JsonResponse {
        if (! Tenant::query()->find($tenant)) {
            return $this->error('TENANT_NOT_FOUND', 'Tenant not found.', 404);
        }

        try {
            return $lock->run($tenant, function () use ($tenant, $webhooks): JsonResponse {
                return DB::transaction(function () use ($tenant, $webhooks): JsonResponse {
                    $current = Tenant::query()->find($tenant);
                    if (! $current) {
                        return $this->error('TENANT_NOT_FOUND', 'Tenant not found.', 404);
                    }
                    if ($current->deletion_started_at !== null) {
                        return $this->error('TENANT_DELETION_STARTED', 'Tenant deletion has started.', 410);
                    }
                    if ($current->provisioning_status === 'provisioning') {
                        return $this->error('TENANT_BUSY', 'Tenant is still being provisioned.', 409);
                    }

                    $changed = $current->is_active || $current->provisioning_status !== 'suspended';
                    if ($changed) {
                        $current->update([
                            'is_active' => false,
                            'provisioning_status' => 'suspended',
                        ]);

                        if ($current->external_subscription_id) {
                            $webhooks->dispatch('tenant.suspended', $current->external_subscription_id, [
                                'provisioning_request_id' => $current->provisioning_request_id,
                                'user_id' => $current->user_id,
                                'domain' => $current->domains()->value('domain'),
                                'is_active' => false,
                            ], $current);
                        }
                    }

                    return response()->json([
                        'tenant_id' => $current->getTenantKey(),
                        'external_subscription_id' => $current->external_subscription_id,
                        'status' => 'suspended',
                        'is_active' => false,
                    ]);
                });
            });
        } catch (LockTimeoutException) {
            return $this->error('TENANT_BUSY', 'Tenant is busy. Retry the suspension shortly.', 409);
        }
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message, 'errors' => []], $status);
    }
}
