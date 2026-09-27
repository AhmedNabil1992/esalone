<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantEntitlementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'branches_limit' => ['required_without_all:has_ai_subscription,ai_requests_limit', 'integer', 'min:1'],
            'has_ai_subscription' => ['required_without_all:branches_limit,ai_requests_limit', 'boolean'],
            'ai_requests_limit' => ['required_without_all:branches_limit,has_ai_subscription', 'integer', 'min:0'],
        ];
    }
}
