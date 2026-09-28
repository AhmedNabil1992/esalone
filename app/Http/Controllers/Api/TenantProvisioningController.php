<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreTenantRequest;
use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Models\TenantProvisioningRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantProvisioningController extends Controller
{
    public function __invoke(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['started_at'] = CarbonImmutable::parse($data['started_at'])->utc()->toIso8601String();
        $data['ended_at'] = CarbonImmutable::parse($data['ended_at'])->utc()->toIso8601String();

        if (
            TenantProvisioningRequest::query()->where('external_subscription_id', $data['external_subscription_id'])->exists()
            || Tenant::query()->where('external_subscription_id', $data['external_subscription_id'])->exists()
        ) {
            return $this->conflict(
                'SUBSCRIPTION_ALREADY_PROVISIONED',
                'This external subscription already has a provisioning request.'
            );
        }

        if (
            TenantProvisioningRequest::query()->where('domain', $data['domain'])->exists()
            || DB::table('domains')->where('domain', $data['domain'])->exists()
        ) {
            return $this->conflict('DOMAIN_ALREADY_TAKEN', 'The requested domain is already reserved.');
        }

        try {
            $provisioningRequest = DB::transaction(function () use ($data): TenantProvisioningRequest {
                return TenantProvisioningRequest::query()->create([
                    'id' => (string) Str::ulid(),
                    'external_subscription_id' => $data['external_subscription_id'],
                    'domain' => $data['domain'],
                    'user_id' => $data['user_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'payload' => $data,
                    'status' => 'pending',
                ]);
            });
        } catch (QueryException $exception) {
            $databaseError = $exception->getMessage();
            if (str_contains($databaseError, 'external_subscription')) {
                return $this->conflict(
                    'SUBSCRIPTION_ALREADY_PROVISIONED',
                    'This external subscription already has a provisioning request.'
                );
            }

            if (str_contains($databaseError, 'domain')) {
                return $this->conflict('DOMAIN_ALREADY_TAKEN', 'The requested domain is already reserved.');
            }

            throw $exception;
        }

        $data['provisioning_request_id'] = $provisioningRequest->getKey();
        ProvisionTenantJob::dispatch($data);

        return response()->json([
            'message' => 'Tenant provisioning request has been queued.',
            'external_subscription_id' => $data['external_subscription_id'],
            'status' => 'queued',
            'domain' => $data['domain'],
        ], 202);
    }

    private function conflict(string $errorCode, string $message): JsonResponse
    {
        return response()->json([
            'error_code' => $errorCode,
            'message' => $message,
            'errors' => [],
        ], 409);
    }
}
