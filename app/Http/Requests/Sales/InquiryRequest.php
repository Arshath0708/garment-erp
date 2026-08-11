<?php

namespace App\Http\Requests\Sales;

use App\Models\Inquiry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Shared rules for creating and updating an inquiry.
 *
 * `mode` (posted as a hidden field, set by the Save Draft / Submit buttons in
 * _form.blade.php's script) decides how strict this pass is: Save Draft
 * always forces `status=draft` on the client before submit, and lets the
 * header/format/currency fields go in blank so a half-filled inquiry can
 * still be saved. Submit requires them.
 */
abstract class InquiryRequest extends FormRequest
{
    abstract protected function permission(): string;

    public function authorize(): bool
    {
        return $this->user()->can($this->permission());
    }

    /**
     * Change request #8 — same "hidden field, blanked server-side" treatment
     * as SupplierRequest's gst_number: the form only shows source_other when
     * source is "other", so a value left behind by an earlier choice is
     * dropped here rather than saved.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('source') !== 'other') {
            $this->merge(['source_other' => null]);
        }
    }

    public function rules(): array
    {
        $requiredUnlessDraft = 'required_unless:mode,draft';

        return [
            'mode' => ['required', Rule::in(['draft', 'submit'])],

            'inquiry_date'   => ['required', 'date'],
            'buyer_ref'      => ['nullable', 'string', 'max:100'],
            'source'         => [$requiredUnlessDraft, 'nullable', Rule::in(array_keys(Inquiry::SOURCES))],
            // Change request #8 — free text captured only when source is "other".
            'source_other'   => ['required_if:source,other', 'nullable', 'string', 'max:150'],
            'buyer_id'       => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('buyers', 'id')],
            'category_id'    => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('categories', 'id')],
            'document_format_id' => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('document_formats', 'id')],

            'agent_id'               => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'agent_commission_type'  => ['nullable', 'required_with:agent_commission_value', Rule::in(['percent', 'flat'])],
            'agent_commission_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],

            'currency_id'    => [$requiredUnlessDraft, 'nullable', 'integer', Rule::exists('currencies', 'id')],
            'exchange_rate'  => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],

            'expected_shipment_date' => ['nullable', 'date'],

            'delivery_details' => [$requiredUnlessDraft, 'nullable', 'string'],
            'packing_details'  => [$requiredUnlessDraft, 'nullable', 'string'],
            'remarks'          => ['nullable', 'string', 'max:2000'],

            'status' => ['required', Rule::in(array_keys(Inquiry::STATUSES))],

            'items'                        => ['nullable', 'array', 'max:200'],
            'items.*.design_no'            => ['nullable', 'string', 'max:150'],
            'items.*.description'          => ['nullable', 'string', 'max:2000'],
            'items.*.product_id'           => ['nullable', 'integer', Rule::exists('products', 'id')],
            'items.*.supplier_id'          => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'items.*.unit'                 => ['nullable', 'string', 'max:20'],
            'items.*.fob_value_id'         => ['nullable', 'integer', Rule::exists('fob_values', 'id')],
            'items.*.price'                => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.cost_price'           => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.status'               => ['nullable', Rule::in(array_keys(Inquiry::STATUSES))],
            'items.*.remarks'              => ['nullable', 'string', 'max:1000'],

            'items.*.colours'                    => ['nullable', 'array', 'max:50'],
            'items.*.colours.*.colour'           => ['nullable', 'string', 'max:60'],
            'items.*.colours.*.sizes'            => ['nullable', 'array', 'max:50'],
            'items.*.colours.*.sizes.*.size'     => ['nullable', 'string', 'max:20'],
            'items.*.colours.*.sizes.*.qty'      => ['nullable', 'integer', 'min:0', 'max:999999'],

            // Order Format custom columns — keyed by DocumentFormatColumn::key,
            // one value per column the format defines beyond the standard set.
            'items.*.custom'   => ['nullable', 'array', 'max:20'],
            'items.*.custom.*' => ['nullable', 'string', 'max:255'],

            'followups'                => ['nullable', 'array', 'max:100'],
            'followups.*.id'           => ['nullable', 'integer'],
            'followups.*.date'         => ['nullable', 'date', 'required_with:followups.*.comment'],
            'followups.*.comment'      => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * A Submit with no item rows is an inquiry about nothing — Save Draft is
     * the button for "not ready yet", and no single-field rule can express
     * "required, but only on the other button".
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') === 'submit' && blank(array_filter((array) $this->input('items', [])))) {
                $validator->errors()->add('items', 'Add at least one item before submitting the inquiry.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'buyer_id'            => 'buyer',
            'category_id'         => 'category',
            'document_format_id'  => 'order format',
            'currency_id'         => 'currency',
            'delivery_details'    => 'delivery details',
            'packing_details'     => 'packing details',
        ];
    }
}
