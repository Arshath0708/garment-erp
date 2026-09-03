<?php

namespace App\Http\Requests\Manufacturing;

use Illuminate\Foundation\Http\FormRequest;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wo_date'               => ['required', 'date'],
            'garment_style_id'      => ['required', 'integer', 'exists:garment_styles,id'],
            'order_confirmation_id' => ['nullable', 'integer', 'exists:order_confirmations,id'],
            'total_qty'             => ['required', 'integer', 'min:1'],
            'target_date'           => ['required', 'date'],
            'notes'                 => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'garment_style_id.required' => 'Pick the garment style this work order is for.',
            'target_date.required'      => 'Target / delivery date is used to plan Time & Action dates.',
        ];
    }
}
