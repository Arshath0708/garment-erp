<?php

namespace App\Http\Requests\Sales;

use App\Models\OrderConfirmation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for creating and updating an OC.
 *
 * Unlike Inquiry, there's no separate "mode" posted field for draft-vs-strict
 * validation — OC's own `status` already carries Draft/Sent/Confirmed, so
 * the three save buttons (Save Draft / Mark OC Sent / Buyer Confirmed) just
 * post different status values and `required_unless:status,draft` reads
 * straight off that.
 */
abstract class OrderConfirmationRequest extends FormRequest
{
    abstract protected function permission(): string;

    public function authorize(): bool
    {
        return $this->user()->can($this->permission());
    }

    public function rules(): array
    {
        $requiredUnlessDraft = 'required_unless:status,draft';

        return [
            'mode'           => ['required', Rule::in(array_keys(OrderConfirmation::MODES))],
            'oc_date'        => ['required', 'date'],
            'buyer_ref'      => ['nullable', 'string', 'max:100'],
            'buyer_id'       => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('buyers', 'id')],
            'category_id'    => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('categories', 'id')],
            'document_format_id' => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('document_formats', 'id')],

            'agent_id'               => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'agent_commission_type'  => ['nullable', 'required_with:agent_commission_value', Rule::in(['percent', 'flat'])],
            'agent_commission_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],

            'currency_id' => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('currencies', 'id')],
            'incoterm'    => ['nullable', 'string', 'max:20'],

            'ship_method'    => ['nullable', 'string', 'max:60'],
            'shipment_date'  => ['nullable', 'string', 'max:60'],
            'pol'            => ['nullable', 'string', 'max:120'],
            'pod'            => ['nullable', 'string', 'max:120'],
            'payment_terms'  => ['nullable', 'string', 'max:120'],

            'delivery_details' => [$requiredUnlessDraft, 'nullable', 'string'],
            'packing_details'  => [$requiredUnlessDraft, 'nullable', 'string'],
            'remarks'          => ['nullable', 'string', 'max:2000'],

            'status' => ['required', Rule::in(array_keys(OrderConfirmation::STATUSES))],

            'items'                        => ['nullable', 'array', 'max:200'],
            'items.*.design_no'            => ['nullable', 'string', 'max:150'],
            'items.*.description'          => ['nullable', 'string', 'max:2000'],
            'items.*.product_id'           => ['nullable', 'integer', Rule::exists('products', 'id')],
            'items.*.supplier_id'          => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'items.*.unit'                 => ['nullable', 'string', 'max:20'],
            'items.*.fob_value_id'         => ['nullable', 'integer', Rule::exists('fob_values', 'id')],
            'items.*.price'                => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.cost_price'           => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.remarks'              => ['nullable', 'string', 'max:1000'],

            'items.*.colours'                => ['nullable', 'array', 'max:50'],
            'items.*.colours.*.colour'       => ['nullable', 'string', 'max:60'],
            'items.*.colours.*.sizes'        => ['nullable', 'array', 'max:50'],
            'items.*.colours.*.sizes.*.size' => ['nullable', 'string', 'max:20'],
            'items.*.colours.*.sizes.*.qty'  => ['nullable', 'integer', 'min:0', 'max:999999'],

            'items.*.custom'   => ['nullable', 'array', 'max:20'],
            'items.*.custom.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * An OC document (mode=oc) that's Sent or Confirmed needs items — a
     * direct contract does not, its items are entered later at PO stage.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $needsItems = $this->input('mode') !== 'direct' && $this->input('status') !== 'draft';

            if ($needsItems && blank(array_filter((array) $this->input('items', [])))) {
                $validator->errors()->add('items', 'Add at least one item before sending the OC.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'buyer_id'           => 'buyer',
            'category_id'        => 'category',
            'document_format_id' => 'order format',
            'currency_id'        => 'currency',
            'delivery_details'   => 'delivery details',
            'packing_details'    => 'packing details',
        ];
    }
}
