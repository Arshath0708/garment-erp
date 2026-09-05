<?php

namespace App\Http\Requests\Communication;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWhatsappSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('whatsapp.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['nullable', 'boolean'],
            'phone_number_id' => ['nullable', 'string', 'max:40'],
            'access_token' => ['nullable', 'string', 'max:4000'],
            'graph_version' => ['required', 'string', 'max:20', 'regex:/^v\d+\.\d+$/'],
            'country_code' => ['required', 'string', 'max:5'],
        ];
    }
}
