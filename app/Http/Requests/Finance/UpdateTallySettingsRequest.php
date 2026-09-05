<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTallySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tally.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_enabled'              => ['nullable', 'boolean'],
            'host_url'                => ['required', 'string', 'max:255'],
            'company_name'            => ['nullable', 'string', 'max:120'],
            'sales_voucher_type'      => ['required', 'string', 'max:80'],
            'debit_note_voucher_type' => ['required', 'string', 'max:80'],
            'sales_ledger'            => ['required', 'string', 'max:120'],
            'igst_ledger'             => ['required', 'string', 'max:120'],
            'job_work_ledger'         => ['required', 'string', 'max:120'],
        ];
    }
}
