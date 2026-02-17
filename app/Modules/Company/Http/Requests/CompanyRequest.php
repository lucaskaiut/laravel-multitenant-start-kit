<?php

declare(strict_types=1);

namespace App\Modules\Company\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->route('id')
            ?? $this->route('company')
            ?? $this->input('id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('companies', 'email')->ignore($companyId),
            ],
            'phone' => ['required', 'string', 'max:255'],
        ];
    }
}
