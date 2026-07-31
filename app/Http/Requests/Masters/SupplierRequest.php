<?php

namespace App\Http\Requests\Masters;

use App\Models\Supplier;
use App\Models\SupplierType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for creating and updating a supplier.
 *
 * Store and Update differ in exactly two ways — the permission checked, and
 * whether the uniqueness rule ignores the current row — so those are the two
 * things the subclasses supply. Same shape as BuyerRequest and ProductRequest.
 */
abstract class SupplierRequest extends FormRequest
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
     * Normalisation, and the four conditional fields the sheet describes.
     *
     * Every "this only applies when…" field is BLANKED here rather than
     * rejected in rules(). The sheet's own wording is "GST number, only if the
     * registered option is chosen" — a user who fills the GST number, then
     * switches the type to unregistered, has answered the question; telling
     * them off for a field the form had already hidden in front of them would
     * be blaming them for the UI. Same reasoning as the buyer's orphaned city.
     */
    protected function prepareForValidation(): void
    {
        // A display code is compared case-insensitively by MySQL's default
        // collation, so "abc" and "ABC" already collide. Upper-casing here means
        // the stored value matches what the duplicate check showed the user.
        foreach (['display_code', 'gst_number', 'pan_number', 'ifsc_code', 'msme_registration_no'] as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => strtoupper(trim($this->string($field)->toString()))]);
            }
        }

        // Checkboxes post nothing when unticked; each is paired with a hidden
        // "0" on the form, so this is normalising "0"/"1" rather than absence.
        $this->merge([
            'is_msme'                  => $this->boolean('is_msme'),
            'we_supply_material'       => $this->boolean('we_supply_material'),
            'requires_sample_approval' => $this->boolean('requires_sample_approval'),
        ]);

        // Col E — "only if the registered option is chosen".
        if (! $this->supplierTypeIsRegistered()) {
            $this->merge(['gst_number' => null]);
        }

        // Col G — the registration number only exists if the answer was yes.
        if (! $this->boolean('is_msme')) {
            $this->merge(['msme_registration_no' => null]);
        }

        /*
         * Jobwork-only flags, DATABASE_SCHEMA.md §5. A trading supplier
         * arranges its own material and rarely needs a sample approved, so
         * leaving either flag set on one would switch on a Material Issue that
         * has no business existing for that party.
         */
        if (! in_array($this->input('party_type'), ['jobber', 'both'], true)) {
            $this->merge([
                'we_supply_material'       => false,
                'requires_sample_approval' => false,
            ]);
        }

        /*
         * A child with no parent is dropped rather than rejected. Clearing the
         * country is a deliberate act, and answering it with "that state does
         * not belong to the selected country" would blame the user for a field
         * the cascade had already emptied in front of them.
         */
        if (blank($this->input('country_id'))) {
            $this->merge(['state_id' => null, 'city_id' => null]);
        }

        if (blank($this->input('state_id'))) {
            $this->merge(['city_id' => null]);
        }

        // An empty commission value with a type selected is not a commission.
        // Blanking both keeps agent_commission_label() from rendering "%".
        if (blank($this->input('agent_commission_value'))) {
            $this->merge(['agent_commission_type' => null]);
        }
    }

    /**
     * Uniqueness deliberately does NOT exclude soft-deleted rows — the database
     * index does not either, so excluding them here would let validation pass
     * and the insert then fail with a duplicate-key error. Same call as
     * StoreCategoryRequest and BuyerRequest.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $ignore = $this->ignoreId();

        return [
            // Col A — "it should tell me if a certain code is used already"
            'display_code' => ['required', 'string', 'max:5', 'regex:/^[A-Z0-9-]+$/', Rule::unique('suppliers', 'display_code')->ignore($ignore)],

            // Not on the sheet — DATABASE_SCHEMA.md §5. Decides which screen
            // lists this party and which of the fields below apply.
            'party_type'   => ['required', Rule::in(array_keys(Supplier::PARTY_TYPES))],

            // Cols B, C
            'company_name' => ['required', 'string', 'max:200'],
            'name_on_bill' => ['nullable', 'string', 'max:200'],

            /*
             * Col D. Inactive types are excluded: a type the client has retired
             * must not be selectable on a new supplier, while suppliers already
             * pointing at it keep rendering.
             */
            'supplier_type_id' => [
                'nullable', 'integer',
                Rule::exists('supplier_types', 'id')->where('status', 'active'),
            ],

            /*
             * Col E. Required once a registered type is chosen — that is the
             * whole point of the conditional; a registered supplier without a
             * GSTIN cannot be billed. prepareForValidation() has already nulled
             * it for the unregistered case, so this rule cannot fire there.
             *
             * The format is the statutory GSTIN structure: 2-digit state code,
             * the 10-character PAN, an entity number, a literal Z, a checksum.
             * It is fixed by law, not a house style, so validating it here
             * catches a typo that would otherwise surface on a filed return.
             */
            'gst_number' => [
                Rule::requiredIf(fn () => $this->supplierTypeIsRegistered()),
                'nullable', 'string', 'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][0-9A-Z]Z[0-9A-Z]$/',
            ],

            // Col F — the statutory PAN structure.
            'pan_number' => ['nullable', 'string', 'size:10', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/'],

            // Col G
            'is_msme'              => ['boolean'],
            'msme_registration_no' => ['required_if:is_msme,true', 'nullable', 'string', 'max:40'],

            // Cols H–K — the primary contact. Stored as a supplier_contacts row.
            'contact_name'           => ['nullable', 'string', 'max:120'],
            'contact_designation_id' => ['nullable', 'integer', Rule::exists('designations', 'id')->where('status', 'active')],
            'contact_email'          => ['nullable', 'email', 'max:150'],
            'contact_mobile'         => ['nullable', 'string', 'max:30'],

            /*
             * Col L — "other contact persons ... name, designation, phone number
             * need to come again to be filled".
             *
             * A row with contact details but no name is the one combination
             * worth rejecting: it saves a phone number nobody can attribute.
             * A wholly blank row is a line the user left alone and is dropped
             * by the service, not reported.
             */
            'contacts'                   => ['nullable', 'array', 'max:20'],
            'contacts.*.name'            => ['required_with:contacts.*.mobile,contacts.*.email,contacts.*.designation_id', 'nullable', 'string', 'max:120'],
            'contacts.*.designation_id'  => ['nullable', 'integer', Rule::exists('designations', 'id')->where('status', 'active')],
            'contacts.*.mobile'          => ['nullable', 'string', 'max:30'],
            'contacts.*.email'           => ['nullable', 'email', 'max:150'],

            // Cols M–Q
            'address'    => ['nullable', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],

            /*
             * Cols O and N. Each is checked against its parent, not just against
             * its own table: the cascade narrows the list in the browser, but a
             * hand-posted state_id from a different country would otherwise save
             * a supplier in "Tamil Nadu, United Kingdom".
             */
            'state_id' => [
                'nullable', 'integer',
                Rule::exists('states', 'id')->where(
                    fn ($q) => $q->where('country_id', $this->input('country_id'))
                ),
            ],
            'city_id' => [
                'nullable', 'integer',
                Rule::exists('cities', 'id')->where(
                    fn ($q) => $q->where('state_id', $this->input('state_id'))
                ),
            ],

            'pincode' => ['nullable', 'string', 'max:20'],

            // Col R — multi-select, connected to the category master
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['integer', Rule::exists('categories', 'id')],

            // Col S — "maximum 2 characters allowed", so 0–99.
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:99.99'],

            // Col T — "maximum 3 characters allowed", so 0–999 days.
            'credit_days'      => ['nullable', 'integer', 'min:0', 'max:999'],

            // Cols U–W. IFSC is the RBI's fixed 11-character format.
            'bank_name'      => ['nullable', 'string', 'max:120'],
            'account_number' => ['nullable', 'string', 'max:40'],
            'ifsc_code'      => ['nullable', 'string', 'size:11', 'regex:/^[A-Z]{4}0[A-Z0-9]{6}$/'],

            /*
             * Col X. The exists rule re-applies the side filter the dropdown
             * shows. Without it, a buyer-side agent id posted by hand would save
             * perfectly happily — the select is a UI filter, not a constraint.
             */
            'agent_id' => [
                'nullable', 'integer',
                Rule::exists('agents', 'id')
                    ->whereIn('agent_type', $this->allowedAgentSides())
                    ->whereNull('deleted_at'),
            ],

            // Col Y
            'agent_commission_type'  => ['nullable', 'required_with:agent_commission_value', Rule::in(['percent', 'amount'])],
            'agent_commission_value' => ['nullable', 'numeric', 'min:0', 'max:99999999.9999'],

            // Jobwork-only — DATABASE_SCHEMA.md §5, not on the sheet.
            'we_supply_material'       => ['boolean'],
            'requires_sample_approval' => ['boolean'],
            'default_delivery_mode'    => ['required', Rule::in(array_keys(Supplier::DELIVERY_MODES))],

            // Cols Z, AA
            'status'  => ['required', Rule::in(['active', 'inactive'])],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * A GSTIN contains the holder's PAN at characters 3–12. Two fields on the
     * same form that must agree is a typo waiting to happen, and the mismatch
     * is invisible until a return is filed — so it is worth one comparison here
     * rather than a correction later.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $gst = $this->input('gst_number');
            $pan = $this->input('pan_number');

            if (blank($gst) || blank($pan) || strlen((string) $gst) !== 15) {
                return;
            }

            if (substr((string) $gst, 2, 10) !== $pan) {
                $validator->errors()->add(
                    'pan_number',
                    'The PAN does not match the one inside the GST number ('.substr((string) $gst, 2, 10).').'
                );
            }
        });
    }

    /**
     * Which sides of the Agent master this party's agent may come from.
     *
     * Read from the same constant the dropdown uses, so the list the user is
     * shown and the rule that enforces it cannot drift. An unrecognised
     * party_type falls back to the sheet's own answer — the party_type rule
     * rejects it anyway, and this must not become a way to reach every agent.
     *
     * @return array<int, string>
     */
    private function allowedAgentSides(): array
    {
        return Supplier::AGENT_SIDES[$this->input('party_type')] ?? ['supplier'];
    }

    /**
     * Whether the chosen supplier type is one that carries a GST number.
     *
     * Runs during prepareForValidation(), so supplier_type_id has not been
     * validated yet — an unknown id resolves to null and is treated as "not
     * registered". The exists rule reports the bad id; this must not throw
     * before it gets the chance.
     */
    private function supplierTypeIsRegistered(): bool
    {
        $id = $this->input('supplier_type_id');

        if (blank($id) || ! is_numeric($id)) {
            return false;
        }

        return (bool) SupplierType::whereKey((int) $id)->value('is_registered');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'display_code'             => 'display code',
            'party_type'               => 'party type',
            'company_name'             => 'company name',
            'name_on_bill'             => 'name on bill',
            'supplier_type_id'         => 'supplier type',
            'gst_number'               => 'GST number',
            'pan_number'               => 'PAN number',
            'is_msme'                  => 'MSME registration',
            'msme_registration_no'     => 'MSME registration number',
            'contact_name'             => 'contact name',
            'contact_designation_id'   => 'designation',
            'contact_email'            => 'email',
            'contact_mobile'           => 'mobile',
            'country_id'               => 'country',
            'state_id'                 => 'state',
            'city_id'                  => 'city',
            'category_ids'             => 'product category',
            'discount_percent'         => 'discount %',
            'credit_days'              => 'credit terms',
            'ifsc_code'                => 'IFSC code',
            'agent_id'                 => 'agent',
            'agent_commission_type'    => 'commission type',
            'agent_commission_value'   => 'agent commission',
            'default_delivery_mode'    => 'delivery mode',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_code.unique' => 'This display code is already used by another supplier.',
            'display_code.regex'  => 'Display code may contain letters, numbers and hyphens only.',
            'display_code.max'    => 'Display code may not be longer than 5 characters.',

            'gst_number.required' => 'A GST number is required for a registered supplier type.',
            'gst_number.regex'    => 'That is not a valid GSTIN — expected 15 characters, e.g. 33ABCDE1234F1Z5.',
            'gst_number.size'     => 'A GSTIN is exactly 15 characters.',
            'pan_number.regex'    => 'That is not a valid PAN — expected 10 characters, e.g. ABCDE1234F.',
            'pan_number.size'     => 'A PAN is exactly 10 characters.',
            'ifsc_code.regex'     => 'That is not a valid IFSC code — expected 11 characters, e.g. HDFC0001234.',
            'ifsc_code.size'      => 'An IFSC code is exactly 11 characters.',

            'msme_registration_no.required_if' => 'Enter the MSME registration number, or untick MSME registered.',

            'discount_percent.max' => 'The discount may not be more than 99.99% — the sheet allows two characters.',
            'credit_days.max'      => 'Credit terms may not be more than 999 days — the sheet allows three characters.',

            'agent_id.exists'  => 'That agent is not available for this party type — check the Agent master.',
            'state_id.exists'  => 'That state does not belong to the selected country.',
            'city_id.exists'   => 'That city does not belong to the selected state.',

            'agent_commission_type.required_with' => 'Choose whether the commission is a percentage or an amount.',
            'contacts.*.name.required_with'       => 'Enter a name for this contact, or clear the row.',
        ];
    }
}
