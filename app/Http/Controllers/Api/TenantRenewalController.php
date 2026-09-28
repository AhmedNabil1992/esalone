<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RenewTenantRequest;
use App\Models\Tenant;
use App\Services\TenantWebhookService;
use App\Tenancy\TenantLifecycleLock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantRenewalController extends Controller
{
    public function __invoke(
        RenewTenantRequest $request,
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
                if ($current->ended_at && now()->greaterThanOrEqualTo(
                    $current->ended_at->addDays((int) config('tenancy.expiration_grace_days', 3))
                )) {
                    return $this->error('RENEWAL_GRACE_PERIOD_ENDED', 'The renewal grace period has ended.', 410);
                }

                $endedAt = CarbonImmutable::parse($request->validated('ended_at'))->utc();
                if ($current->ended_at && $endedAt->isBefore($current->ended_at)) {
                    return response()->json([
                        'error_code' => 'INVALID_ENTITLEMENTS',
                        'message' => 'The renewal cannot shorten the current subscription.',
                        'errors' => ['ended_at' => ['The renewal cannot shorten the current subscription.']],
                    ], 422);
                }

                $current->update([
                    'ended_at' => $endedAt,
                    'is_active' => true,
                    'provisioning_status' => 'ready',
                ]);

                if ($current->external_subscription_id) {
                    $webhooks->dispatch('tenant.renewed', $current->external_subscription_id, [
                        'provisioning_request_id' => $current->provisioning_request_id,
                        'user_id' => $current->user_id,
                        'domain' => $current->domains()->value('domain'),
                        'ended_at' => $current->ended_at?->utc()->toIso8601String(),
                        'is_active' => true,
                    ], $current);
                }

                return response()->json([
                    'tenant_id' => $current->getTenantKey(),
                    'external_subscription_id' => $current->external_subscription_id,
                    'ended_at' => $current->ended_at?->utc()->toIso8601String(),
                    'is_active' => $current->is_active,
                ]);
            });
        } catch (LockTimeoutException) {
            return $this->error('TENANT_BUSY', 'Tenant is busy. Retry the renewal shortly.', 409);
        }
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error_code' => $code, 'message' => $message, 'errors' => []], $status);
    }
}
