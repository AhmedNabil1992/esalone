<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Tenant;
use App\Tenancy\TenantLifecycleLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Throwable;

class TenantDeletionController extends Controller
{
    public function __invoke(Tenant $tenant): JsonResponse
    {
        $tenantId = (string) $tenant->getTenantKey();

        try {

            app(TenantLifecycleLock::class)->run($tenantId, function () use ($tenant): void {
                $tenant->fresh()?->delete();
            });

            return response()->json([
                'message' => 'Tenant deleted successfully.',
                'tenant_id' => $tenantId,
            ]);
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'Failed to delete tenant.',
                'error' => $throwable->getMessage(),
            ], 500);
        }
    }
}
