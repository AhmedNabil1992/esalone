<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Models\TenantProvisioningRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantStatusController extends Controller
{
    public function __invoke(string $tenantId): JsonResponse
    {
        $tenant = Tenant::query()->find($tenantId);
        if (! $tenant) {
            $request = TenantProvisioningRequest::query()->where('tenant_id', $tenantId)->first();
            if ($request && $request->status === 'deleted') {
                $payload = $request->getAttribute('payload');
                $payload = is_array($payload) ? $payload : [];
                $completedAt = $request->getAttribute('completed_at');

                return response()->json([
                    'tenant_id' => $tenantId,
                    'provisioning_request_id' => $request->getKey(),
                    'external_subscription_id' => $request->external_subscription_id,
                    'status' => 'deleted',
                    'domain' => $request->domain,
                    'login_url' => null,
                    'started_at' => $payload['started_at'] ?? null,
                    'ended_at' => $payload['ended_at'] ?? null,
                    'error_code' => null,
                    'error_message' => null,
                    'retryable' => false,
                    'completed_at' => $completedAt !== null
                        ? CarbonImmutable::parse($completedAt)->utc()->toIso8601String()
                        : null,
                ]);
            }

            return response()->json([
                'error_code' => 'TENANT_NOT_FOUND',
                'message' => 'Tenant not found.',
                'errors' => [],
            ], 404);
        }

        $domain = $tenant->domains()->value('domain');
        $status = (string) ($tenant->provisioning_status ?? 'pending');
        if ($tenant->deletion_started_at !== null) {
            $status = 'deleting';
        } elseif ($status === 'ready' && ! $tenant->is_active) {
            $status = 'suspended';
        }

        return response()->json([
            'tenant_id' => $tenant->getTenantKey(),
            'provisioning_request_id' => $tenant->provisioning_request_id,
            'external_subscription_id' => $tenant->external_subscription_id,
            'status' => $status,
            'domain' => $domain,
            'login_url' => $domain ? 'https://'.$domain.'/admin/login' : null,
            'started_at' => $tenant->started_at?->utc()->toIso8601String(),
            'ended_at' => $tenant->ended_at?->utc()->toIso8601String(),
            'error_code' => $tenant->error_code,
            'error_message' => $tenant->error_message,
            'retryable' => (bool) $tenant->retryable,
            'completed_at' => $tenant->completed_at?->utc()->toIso8601String(),
        ]);
    }
}
