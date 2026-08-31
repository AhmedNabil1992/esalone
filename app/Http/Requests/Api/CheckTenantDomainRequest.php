<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CheckTenantDomainRequest extends FormRequest
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'domain.required' => 'حقل الدومين مطلوب.',
            'domain.string' => 'يجب أن يكون الدومين نصاً صالحاً.',
            'domain.max' => 'يجب ألا يتجاوز طول الدومين 255 حرفاً.',
        ];
    }
}
