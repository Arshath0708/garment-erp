<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for the Markup master.
 *
 * `record_date` is absent on purpose. The prototype captions it
 * "Auto-generated on record creation" and renders it read-only, so the service
 * sets it and the request never accepts one.
 *
 * `discount_percent` is absent for the same class of reason: it belongs to the
 * supplier and is shown here for context only — "Auto-filled from Supplier
 * Master · edit there to change".
 */
abstract class MarkupRequest extends FormRequest
{
    abstract protected function permission(): string;

    abstract protected function ignoreId(): ?int;

    public function authorize(): bool
    {
        return $this->user()->can($this->permission());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /*
             * Only active parties. A rule written against a supplier that has
             * been retired cannot price anything, and offering them in the
             * dropdown would be the only way to reach this branch.
             */
            'supplier_id' => [
                'required', 'integer',
                Rule::exists('suppliers', 'id')->whereNull('deleted_at')->where('status', 'active'),
            ],
            'buyer_id' => [
                'required', 'integer',
                Rule::exists('buyers', 'id')->whereNull('deleted_at')->where('status', 'active'),
                /*
                 * One rule per pair — "Applied per CP at OC / PO entry" has to
                 * resolve to exactly one answer.
                 *
                 * Deliberately NOT excluding soft-deleted rows: the database
                 * index does not either, so excluding them here would let
                 * validation pass and the insert then fail on a duplicate key.
                 * Restore the deleted rule rather than recreating it.
                 */
                Rule::unique('markups', 'buyer_id')
                    ->where('supplier_id', $this->input('supplier_id'))
                    ->ignore($this->ignoreId()),
            ],

            // Below 0 is a discount, not a markup, and there is a supplier
            // field for that. 999.99 is the column's width.
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:999.99'],

            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'buyer_id.unique'   => 'A markup rule already exists for this supplier and buyer.',
            'supplier_id.exists' => 'Select an active supplier.',
            'buyer_id.exists'    => 'Select an active buyer.',
        ];
    }
}
