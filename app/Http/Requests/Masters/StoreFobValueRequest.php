<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFobValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('fob-value.create');
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:120', Rule::unique('fob_values', 'name')],
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
