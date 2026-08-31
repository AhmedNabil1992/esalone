<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CheckTenantDomainRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Stancl\Tenancy\Database\Models\Domain;

class TenantDomainCheckController extends Controller
{
    public function __invoke(CheckTenantDomainRequest $request): JsonResponse
    {
        $domain = trim((string) $request->validated('domain'));

        /** @var class-string<Domain> $domainModel */
        $domainModel = config('tenancy.domain_model', Domain::class);

        $exists = $domainModel::query()->where('domain', $domain)->exists();

        return response()->json([
            'domain' => $domain,
            'exists' => $exists,
            'available' => ! $exists,
            'message' => $exists ? 'الدومين مستخدم بالفعل' : 'الدومين متاح للاستخدام',
        ]);
    }
}
