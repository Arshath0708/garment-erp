<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;

class StyleCostingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'costing_date'          => ['required', 'date'],
            'garment_style_id'      => ['required', 'integer', 'exists:garment_styles,id'],
            'cm_cost'               => ['nullable', 'numeric', 'min:0'],
            'other_cost'            => ['nullable', 'numeric', 'min:0'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
            'lines'                 => ['nullable', 'array'],
            'lines.*.product_id'    => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description'   => ['nullable', 'string', 'max:255'],
            'lines.*.item_kind'     => ['nullable', 'string', 'max:30'],
            'lines.*.qty_per_pc'    => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit'          => ['nullable', 'string', 'max:20'],
            'lines.*.rate'          => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'garment_style_id.required' => 'Pick the garment style this costing is for.',
        ];
    }
}
