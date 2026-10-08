<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Tenant\ResetTenantTrialDataAction;
use App\Models\Tenant;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantTrialDataResetController extends Controller
{
    public function __invoke(
        string $tenant,
        TenantLifecycleLock $lock,
        ResetTenantTrialDataAction $resetTrialData,
    ): JsonResponse {
        $model = Tenant::query()->find($tenant);
        if (! $model) {
            return $this->error('TENANT_NOT_FOUND', 'Tenant not found.', 404);
        }

        try {
            return $lock->run($tenant, function () use ($model, $resetTrialData): JsonResponse {
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

                try {
                    tenancy()->initialize($current);

                    if (! tenancy()->initialized || tenant('id') !== $current->getTenantKey()) {
                        return $this->error('TENANT_CONTEXT_FAILED', 'Tenant context could not be initialized.', 500);
                    }

                    $result = $resetTrialData->execute();
                } finally {
                    tenancy()->end();
                }

                return response()->json([
                    'message' => 'Tenant trial transactions were reset successfully.',
                    'tenant_id' => $current->getTenantKey(),
                    'deleted' => $result['deleted'],
                    'reset' => $result['reset'],
                ]);
            });
        } catch (LockTimeoutException) {
            return $this->error('TENANT_BUSY', 'Tenant is busy. Retry the reset shortly.', 409);
        }
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message, 'errors' => []], $status);
    }
}
