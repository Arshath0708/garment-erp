<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('company-profile.edit');
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'tagline'       => ['nullable', 'string', 'max:255'],
            'address'       => ['nullable', 'string', 'max:1000'],
            'phone'         => ['nullable', 'string', 'max:100'],
            'email'         => ['nullable', 'email', 'max:255'],

            'gstin'    => ['nullable', 'string', 'max:20'],
            'iec_code' => ['nullable', 'string', 'max:20'],

            'bank_name'            => ['nullable', 'string', 'max:150'],
            'bank_account_number'  => ['nullable', 'string', 'max:40'],
            'bank_ifsc'            => ['nullable', 'string', 'max:20'],
            'bank_swift'           => ['nullable', 'string', 'max:20'],

            'signatory_name'        => ['nullable', 'string', 'max:150'],
            'signatory_designation' => ['nullable', 'string', 'max:150'],

            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'company_name'        => 'company name',
            'bank_account_number' => 'bank account number',
            'bank_ifsc'           => 'IFSC code',
            'bank_swift'          => 'SWIFT code',
        ];
    }
}
