<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

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
            'domain' => ['required', 'string', 'max:255', 'unique:domains,domain'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date'],
            'user_id' => ['required', 'integer'],
            'auto_renew' => ['required', 'boolean'],
            'branches_limit' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
