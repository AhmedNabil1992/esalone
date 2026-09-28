<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'started_at' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/'],
            'ended_at' => ['required', 'date', 'after:started_at', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/'],
            'user_id' => ['required', 'integer'],
            'external_subscription_id' => ['required', 'string', 'max:255'],
            'auto_renew' => ['required', 'boolean'],
            'branches_limit' => ['nullable', 'integer', 'min:1'],
            'has_ai_subscription' => ['nullable', 'boolean'],
            'ai_requests_limit' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'error_code' => 'PROVISIONING_FAILED',
            'message' => 'The tenant provisioning request is invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
