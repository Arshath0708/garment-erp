@props([
    'supplier' => null,
    'partyTypes' => [],
    'deliveryModes' => [],
    'supplierTypes' => [],
    'supplierTypeFlags' => [],
    'designations' => [],
    'categories' => [],
    'agents' => [],
    'countries' => [],
    'states' => [],
    'cities' => [],
])

@php
    $selectedCategories = $supplier?->categories->pluck('id')->all() ?? [];
    $primary = $supplier?->contacts->firstWhere('is_primary', true);
    $extraContacts = old('contacts');

    if ($extraContacts === null) {
        $extraContacts = $supplier
            ? $supplier->contacts->where('is_primary', false)->map(fn ($c) => [
                'name'           => $c->name,
                'designation_id' => $c->designation_id,
                'mobile'         => $c->mobile,
                'email'          => $c->email,
            ])->values()->all()
            : [];
    }

    if ($extraContacts === []) {
        $extraContacts = [['name' => null, 'designation_id' => null, 'mobile' => null, 'email' => null]];
    }

    $selectedType = old('supplier_type_id', $supplier?->supplier_type_id);

    $gstVisible = ($supplierTypeFlags[$selectedType] ?? false) === true
        || $errors->has('gst_number');

    $msmeVisible = (bool) old('is_msme', $supplier?->is_msme)
        || $errors->has('msme_registration_no');

    $jobworkVisible = true; // Jobber master always shows jobwork section
@endphp

<input type="hidden" name="party_type" id="party_type" value="jobber">

{{-- ================== A–C · IDENTIFICATION ======================= --}}
<x-ui.form-section title="Identification" icon="bi-tools"
                   subtitle="Who the jobber is and their basic details.">
    <div class="form-stack">

        <div class="row form-line">
            <label for="display_code" class="col-sm-4 col-lg-3 col-form-label fw-semibold required">
                Display Code
            </label>
            <div class="col-sm-8 col-lg-9">
                <input type="text" id="display_code" name="display_code"
                       value="{{ old('display_code', $supplier?->display_code) }}"
                       class="form-control font-monospace text-uppercase @error('display_code') is-invalid @enderror"
                       style="max-width:220px" required placeholder="JOB01"
                       data-check-url="{{ route('masters.jobbers.check-code') }}">
                @error('display_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text js-code-feedback">Short code used on orders and reports. Unique across all suppliers and jobbers.</div>
            </div>
        </div>

        <x-ui.field name="company_name" label="Company Name" required :value="$supplier?->company_name"
                    horizontal maxlength="150" placeholder="e.g. Tirupur Jobwork Crafts" />

        <x-ui.field name="name_on_bill" label="Name on Bill" :value="$supplier?->name_on_bill"
                    horizontal maxlength="150" placeholder="Name as it appears on bills"
                    hint="Leave blank if identical to company name." />

        <div class="row form-line">
            <label for="category_ids" class="col-sm-4 col-lg-3 col-form-label fw-semibold required">
                Product Category
            </label>
            <div class="col-sm-8 col-lg-9">
                <select name="category_ids[]" id="category_ids" multiple searchable required
                        data-placeholder="Search and select categories…"
                        class="form-select @error('category_ids') is-invalid @enderror @error('category_ids.*') is-invalid @enderror">
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}" @selected(in_array($id, old('category_ids', $selectedCategories)))>{{ $name }}</option>
                    @endforeach
                </select>
                @error('category_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @error('category_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

    </div>
</x-ui.form-section>

{{-- ================== D–G · REGISTRATION ========================= --}}
<x-ui.form-section title="Registration" icon="bi-card-heading">
    <div class="form-stack">

        <x-ui.select name="supplier_type_id" label="Jobber Type" required horizontal
                     :options="$supplierTypes" :selected="$selectedType"
                     placeholder="Select type…" />

        <div id="gst-row" class="row form-line {{ $gstVisible ? '' : 'd-none' }}">
            <label for="gst_number" class="col-sm-4 col-lg-3 col-form-label fw-semibold">GST Number</label>
            <div class="col-sm-8 col-lg-9">
                <input type="text" id="gst_number" name="gst_number"
                       value="{{ old('gst_number', $supplier?->gst_number) }}"
                       class="form-control font-monospace text-uppercase @error('gst_number') is-invalid @enderror"
                       style="max-width:260px" maxlength="15" placeholder="33AAAAA0000A1Z5">
                @error('gst_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <x-ui.field name="pan_number" label="PAN Number" :value="$supplier?->pan_number"
                    horizontal maxlength="10" placeholder="AAAAA0000A"
                    class="font-monospace text-uppercase" />

        <div class="row form-line">
            <label class="col-sm-4 col-lg-3 col-form-label fw-semibold">MSME</label>
            <div class="col-sm-8 col-lg-9 pt-2">
                <div class="form-check mb-2">
                    <input type="hidden" name="is_msme" value="0">
                    <input type="checkbox" class="form-check-input" id="is_msme" name="is_msme" value="1"
                           @checked(old('is_msme', $supplier?->is_msme))>
                    <label class="form-check-label" for="is_msme">Registered under MSME / Udyam</label>
                </div>
                <div id="msme-no-wrap" class="{{ $msmeVisible ? '' : 'd-none' }}">
                    <input type="text" id="msme_registration_no" name="msme_registration_no"
                           value="{{ old('msme_registration_no', $supplier?->msme_registration_no) }}"
                           class="form-control font-monospace @error('msme_registration_no') is-invalid @enderror"
                           style="max-width:260px" placeholder="UDYAM-TN-00-0000000">
                    @error('msme_registration_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

    </div>
</x-ui.form-section>

{{-- ================== H–L · CONTACT PERSONS ======================= --}}
<x-ui.form-section title="Contact Persons" icon="bi-people">
    <div class="form-stack">

        <div class="p-3 bg-body-tertiary rounded border mb-3">
            <div class="small fw-semibold text-body-secondary text-uppercase mb-2">Primary Contact</div>
            <div class="row g-2">
                <div class="col-md-3">
                    <label for="primary_name" class="form-label small text-body-secondary mb-1 required">Name</label>
                    <input type="text" id="primary_name" name="primary_contact[name]"
                           value="{{ old('primary_contact.name', $primary?->name) }}"
                           class="form-control form-control-sm @error('primary_contact.name') is-invalid @enderror"
                           required placeholder="Contact Name">
                    @error('primary_contact.name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="primary_designation" class="form-label small text-body-secondary mb-1">Designation</label>
                    <select id="primary_designation" name="primary_contact[designation_id]"
                            class="form-select form-select-sm @error('primary_contact.designation_id') is-invalid @enderror">
                        <option value="">Select…</option>
                        @foreach($designations as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('primary_contact.designation_id', $primary?->designation_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('primary_contact.designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="primary_mobile" class="form-label small text-body-secondary mb-1 required">Mobile</label>
                    <input type="text" id="primary_mobile" name="primary_contact[mobile]"
                           value="{{ old('primary_contact.mobile', $primary?->mobile) }}"
                           class="form-control form-control-sm @error('primary_contact.mobile') is-invalid @enderror"
                           required placeholder="9876543210">
                    @error('primary_contact.mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="primary_email" class="form-label small text-body-secondary mb-1">Email</label>
                    <input type="email" id="primary_email" name="primary_contact[email]"
                           value="{{ old('primary_contact.email', $primary?->email) }}"
                           class="form-control form-control-sm @error('primary_contact.email') is-invalid @enderror"
                           placeholder="contact@jobber.com">
                    @error('primary_contact.email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

    </div>
</x-ui.form-section>

{{-- ================== M–P · LOCATION ============================= --}}
<x-ui.form-section title="Address & Location" icon="bi-geo-alt">
    <div class="form-stack">
        <x-ui.textarea name="address" label="Address" :value="$supplier?->address"
                       horizontal rows="2" placeholder="Factory / Office address" />

        <x-ui.select name="country_id" label="Country" :options="$countries"
                     :selected="$supplier?->country_id" horizontal searchable
                     placeholder="Search country…"
                     data-cascade-child="#state_id" />

        <x-ui.select name="state_id" label="State" :options="$states"
                     :selected="$supplier?->state_id" horizontal searchable
                     placeholder="Search state…"
                     data-cascade-child="#city_id"
                     data-cascade-parent="#country_id"
                     :data-cascade-url="route('masters.geo.states')"
                     data-cascade-key="country_id"
                     data-cascade-empty="Select a country first" />

        <x-ui.select name="city_id" label="City" :options="$cities"
                     :selected="$supplier?->city_id" horizontal searchable
                     placeholder="Search city…"
                     data-cascade-parent="#state_id"
                     :data-cascade-url="route('masters.geo.cities')"
                     data-cascade-key="state_id"
                     data-cascade-empty="Select a state first" />

        <x-ui.field name="pincode" label="PIN Code" :value="$supplier?->pincode"
                    horizontal maxlength="10" placeholder="641601"
                    class="font-monospace" />
    </div>
</x-ui.form-section>

{{-- =================== S–T · TRADE TERMS ======================== --}}
<x-ui.form-section title="Trade Terms" icon="bi-percent">
    <div class="form-stack">
        <div class="row form-line">
            <label for="discount_percent" class="col-sm-4 col-lg-3 col-form-label fw-semibold">Discount %</label>
            <div class="col-sm-8 col-lg-9">
                <div class="input-group" style="max-width:220px">
                    <input type="number" step="0.01" min="0" max="99.99"
                           id="discount_percent" name="discount_percent"
                           value="{{ old('discount_percent', $supplier?->discount_percent) }}"
                           class="form-control @error('discount_percent') is-invalid @enderror"
                           placeholder="0.00">
                    <span class="input-group-text">%</span>
                </div>
                @error('discount_percent')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row form-line">
            <label for="credit_days" class="col-sm-4 col-lg-3 col-form-label fw-semibold">Credit Terms</label>
            <div class="col-sm-8 col-lg-9">
                <div class="input-group" style="max-width:220px">
                    <input type="number" step="1" min="0" max="999"
                           id="credit_days" name="credit_days"
                           value="{{ old('credit_days', $supplier?->credit_days) }}"
                           class="form-control @error('credit_days') is-invalid @enderror"
                           placeholder="45">
                    <span class="input-group-text">days</span>
                </div>
                @error('credit_days')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</x-ui.form-section>

{{-- ================ JOBWORK (schema §5) ==================== --}}
<x-ui.form-section title="Jobwork Options" icon="bi-tools" id="jobwork-section">
    <div class="form-stack">
        <div class="row form-line">
            <label class="col-sm-4 col-lg-3 col-form-label fw-semibold">Material</label>
            <div class="col-sm-8 col-lg-9 pt-2">
                <div class="form-check">
                    <input type="hidden" name="we_supply_material" value="0">
                    <input type="checkbox" class="form-check-input" id="we_supply_material"
                           name="we_supply_material" value="1"
                           @checked(old('we_supply_material', $supplier?->we_supply_material ?? true))>
                    <label class="form-check-label" for="we_supply_material">
                        We supply the material — fabric, trims, labels, packing
                    </label>
                </div>
            </div>
        </div>

        <div class="row form-line">
            <label class="col-sm-4 col-lg-3 col-form-label fw-semibold">Sample</label>
            <div class="col-sm-8 col-lg-9 pt-2">
                <div class="form-check">
                    <input type="hidden" name="requires_sample_approval" value="0">
                    <input type="checkbox" class="form-check-input" id="requires_sample_approval"
                           name="requires_sample_approval" value="1"
                           @checked(old('requires_sample_approval', $supplier?->requires_sample_approval))>
                    <label class="form-check-label" for="requires_sample_approval">
                        A sample must be approved before a PO is raised
                    </label>
                </div>
            </div>
        </div>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Delivery" icon="bi-box-arrow-right">
    <div class="form-stack">
        <x-ui.select name="default_delivery_mode" label="Default Delivery Mode" required horizontal
                     :options="$deliveryModes"
                     :selected="$supplier?->default_delivery_mode ?? 'direct_to_port'"
                     :placeholder="false" />
    </div>
</x-ui.form-section>

{{-- =================== U–W · BANK DETAILS ======================= --}}
<x-ui.form-section title="Bank Details" icon="bi-bank">
    <div class="form-stack">
        <x-ui.field name="bank_name" label="Bank Name" :value="$supplier?->bank_name"
                    horizontal maxlength="120" placeholder="HDFC Bank" />

        <x-ui.field name="account_number" label="Account Number" :value="$supplier?->account_number"
                    horizontal maxlength="40" placeholder="50100123456789"
                    class="font-monospace" />

        <x-ui.field name="ifsc_code" label="IFSC Code" :value="$supplier?->ifsc_code"
                    horizontal maxlength="11" placeholder="HDFC0001234"
                    class="font-monospace text-uppercase" />
    </div>
</x-ui.form-section>

{{-- ====================== X–Y · AGENT =========================== --}}
<x-ui.form-section title="Agent" icon="bi-person-badge">
    <div class="form-stack">
        <x-ui.select name="agent_id" label="Agent" :options="$agents"
                     :selected="$supplier?->agent_id" horizontal searchable
                     placeholder="Search agent…"
                     data-cascade-parent="#party_type"
                     :data-cascade-url="route('masters.jobbers.agents')"
                     data-cascade-key="party_type" />
    </div>
</x-ui.form-section>

{{-- ====================== Z, AA · OTHER ========================= --}}
<x-ui.form-section title="Other Details" icon="bi-card-text">
    <div class="form-stack">
        <x-ui.select name="status" label="Status" required horizontal
                     :options="['active' => 'Active', 'inactive' => 'Inactive']"
                     :selected="$supplier?->status ?? 'active'"
                     :placeholder="false" />

        <x-ui.textarea name="remarks" label="Remarks" :value="$supplier?->remarks"
                       horizontal rows="2" placeholder="Optional notes" />

        <x-ui.textarea name="comments" label="Comments" :value="$supplier?->comments"
                       horizontal rows="2" placeholder="Optional comments" />
    </div>
</x-ui.form-section>

<div class="form-actions">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>{{ $supplier ? 'Update' : 'Save' }} Jobber
    </button>
    <a href="{{ route('masters.jobbers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>
