<?php

namespace App\Http\Requests\Masters;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for creating and updating a buyer.
 *
 * Store and Update differ in exactly two ways — the permission checked, and
 * whether the uniqueness rule ignores the current row — so those are the two
 * things the subclasses supply. Same shape as ProductRequest.
 */
abstract class BuyerRequest extends FormRequest
{
    abstract protected function permission(): string;

    /**
     * Primary key to exclude from the unique check; null when creating.
     */
    abstract protected function ignoreId(): ?int;

    public function authorize(): bool
    {
        return $this->user()->can($this->permission());
    }

    /**
     * A display code is compared case-insensitively by MySQL's default
     * collation, so "abc" and "ABC" already collide. Upper-casing it here means
     * the stored value matches what the user was shown on the duplicate check
     * rather than whichever case they happened to type.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('display_code')) {
            $this->merge(['display_code' => strtoupper(trim($this->string('display_code')->toString()))]);
        }

        // An empty commission value with a type selected is not a commission.
        // Blanking both keeps agent_commission_label() from rendering "%".
        if (blank($this->input('agent_commission_value'))) {
            $this->merge(['agent_commission_type' => null]);
        }

        /*
         * A child with no parent is dropped rather than rejected. Clearing the
         * country is a deliberate act, and answering it with "that state does
         * not belong to the selected country" would be blaming the user for a
         * field the cascade had already emptied in front of them.
         */
        if (blank($this->input('country_id'))) {
            $this->merge(['state_id' => null, 'city_id' => null]);
        }

        if (blank($this->input('state_id'))) {
            $this->merge(['city_id' => null]);
        }
    }

    /**
     * Uniqueness deliberately does NOT exclude soft-deleted rows — the database
     * index does not either, so excluding them here would let validation pass
     * and the insert then fail with a duplicate-key error. See
     * StoreCategoryRequest for the same reasoning.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignore = $this->ignoreId();

        return [
            // Col A — "max 5 characters, tell me if a certain code is used already"
            'display_code'           => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9-]+$/', Rule::unique('buyers', 'display_code')->ignore($ignore)],

            // Cols B, C
            'company_name'           => ['required', 'string', 'max:200'],
            'name_on_export_invoice' => ['nullable', 'string', 'max:200'],

            // Col D — multi-select, connected to the category master
            'category_ids'           => ['nullable', 'array'],
            'category_ids.*'         => ['integer', Rule::exists('categories', 'id')],

            // Cols E, F, G
            'contact_person'         => ['nullable', 'string', 'max:120'],
            'email'                  => ['nullable', 'email', 'max:150'],
            'mobile'                 => ['nullable', 'string', 'max:30'],

            // Cols I–N
            'address'                => ['nullable', 'string', 'max:255'],
            'country_id'             => ['nullable', 'integer', Rule::exists('countries', 'id')],

            /*
             * Cols K and J. Each is checked against its parent, not just against
             * its own table: the cascade narrows the list in the browser, but a
             * hand-posted state_id from a different country would otherwise save
             * a buyer in "Tamil Nadu, United Kingdom".
             *
             * There is no "city requires a state" rule — prepareForValidation
             * has already nulled an orphaned city by this point, so such a rule
             * could never fire.
             */
            'state_id'               => [
                'nullable', 'integer',
                Rule::exists('states', 'id')->where(
                    fn ($q) => $q->where('country_id', $this->input('country_id'))
                ),
            ],
            'city_id'                => [
                'nullable', 'integer',
                Rule::exists('cities', 'id')->where(
                    fn ($q) => $q->where('state_id', $this->input('state_id'))
                ),
            ],

            'pincode'                => ['nullable', 'string', 'max:20'],
            'port_id'                => ['nullable', 'integer', Rule::exists('ports', 'id')],

            /*
             * Col O. The exists rule re-applies the "buyer side only" filter the
             * dropdown shows. Without it, a supplier-side agent id posted by
             * hand would save perfectly happily — the select is a UI filter, not
             * a constraint.
             */
            'agent_id'               => [
                'nullable', 'integer',
                Rule::exists('agent_sides', 'agent_id')->where('side', 'buyer'),
            ],

            // Col P
            'agent_commission_type'  => ['nullable', 'required_with:agent_commission_value', Rule::in(['percent', 'amount'])],
            'agent_commission_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],

            // Cols Q–T
            'payment_term_id'        => ['nullable', 'integer', Rule::exists('payment_terms', 'id')],
            'incoterm_id'            => ['nullable', 'integer', Rule::exists('incoterms', 'id')],
            'shipment_method_id'     => ['nullable', 'integer', Rule::exists('shipment_methods', 'id')],
            'currency_id'            => ['nullable', 'integer', Rule::exists('currencies', 'id')],

            // Cols U–W
            'bank_name'              => ['nullable', 'string', 'max:120'],
            'account_number'         => ['nullable', 'string', 'max:40'],
            'swift_code'             => ['nullable', 'string', 'max:20'],

            // Col X — repeatable carton mark lines
            'carton_markings'        => ['nullable', 'array', 'max:20'],
            'carton_markings.*.label' => ['required_with:carton_markings.*.value', 'nullable', 'string', 'max:60'],
            'carton_markings.*.value' => ['nullable', 'string', 'max:120'],

            // Cols Y, Z
            'status'                 => ['required', Rule::in(['active', 'inactive'])],
            'remarks'                => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'display_code'           => 'display code',
            'company_name'           => 'company name',
            'name_on_export_invoice' => 'company name on export invoice',
            'category_ids'           => 'category of items',
            'country_id'             => 'country',
            'state_id'               => 'state',
            'city_id'                => 'city',
            'port_id'                => 'port',
            'agent_id'               => 'agent',
            'agent_commission_type'  => 'commission type',
            'agent_commission_value' => 'agent commission',
            'payment_term_id'        => 'payment terms',
            'incoterm_id'            => 'inco terms',
            'shipment_method_id'     => 'shipment method',
            'currency_id'            => 'currency of payment',
            'swift_code'             => 'swift code',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_code.unique' => 'This display code is already used by another buyer.',
            'display_code.regex'  => 'Display code may contain letters, numbers and hyphens only.',
            'display_code.max'    => 'Display code may not be longer than 5 characters.',
            'agent_id.exists'     => 'That agent is not marked as a buyer-side agent.',
            'state_id.exists'     => 'That state does not belong to the selected country.',
            'city_id.exists'      => 'That city does not belong to the selected state.',
            'agent_commission_type.required_with' => 'Choose whether the commission is a percentage or an amount.',
        ];
    }
}
