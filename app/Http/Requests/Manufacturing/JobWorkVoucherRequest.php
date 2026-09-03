<?php

namespace App\Http\Requests\Manufacturing;

use App\Models\JobWorkVoucher;
use App\Models\ProductionOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JobWorkVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sizeRules = [];
        foreach (ProductionOrder::SIZES as $size) {
            $sizeRules["sizes.{$size}"] = ['nullable', 'integer', 'min:0'];
        }

        return array_merge([
            'type'                 => ['required', Rule::in(array_keys(JobWorkVoucher::TYPES))],
            'voucher_date'         => ['required', 'date'],
            'jobber_id'            => ['required', 'integer', 'exists:suppliers,id'],
            'production_order_id'  => ['nullable', 'integer', 'exists:production_orders,id'],
            'process'              => ['nullable', 'string', 'max:30'],
            'vehicle_no'           => ['nullable', 'string', 'max:50'],
            'damaged_qty'          => ['nullable', 'integer', 'min:0'],
            'rate_per_pc'          => ['nullable', 'numeric', 'min:0'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'sizes'                => ['nullable', 'array'],
        ], $sizeRules);
    }
}
