<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFobValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fob-value.edit');
    }

    public function rules(): array
    {
        $fobValueId = $this->route('fob_value')?->id ?? $this->route('fob_value');

        return [
            'name'    => ['required', 'string', 'max:120', Rule::unique('fob_values', 'name')->ignore($fobValueId)],
            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'An FOB Value with this name already exists.',
        ];
    }
}
