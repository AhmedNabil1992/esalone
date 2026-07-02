<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\StoreTenantRequest;
use App\Jobs\ProvisionTenantJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class TenantProvisioningController extends Controller
{
    public function __invoke(StoreTenantRequest $request): JsonResponse
    {
        $data = $request->validated();

        ProvisionTenantJob::dispatch($data);

        return response()->json([
            'message' => 'جاري انشاء الموقع الالكتروني وسيتم ارسال بريد الكتروني فور الانتهاء',
        ], 202);
    }
}
