@props([
    'agent' => null,
    'agentTypes' => [],
    'categories' => [],
    'calculationBases' => [],
    'currencies' => [],
    'commissionPayers' => [],
    'paymentTerms' => [],
    'commissionTypes' => [],
])

@php
    /**
     * The side decides which blocks are on screen. Resolved once here so the
     * server-rendered markup already matches what the JS toggle would produce —
     * otherwise a validation round-trip on a buyer-side agent flashes the GST
     * block for as long as it takes the script to run.
     */
    $type     = old('agent_type', $agent?->agent_type ?? 'supplier');
    $domestic = $type !== 'buyer';

    /**
     * Commission rows to render. old() wins so a failed submit keeps what was
     * typed; a new agent starts with one blank row, because the entry is
     * required and an empty table would only show a button.
     */
    $commissionRows = old('commissions');

    if ($commissionRows === null) {
        $commissionRows = $agent?->commissions
            ->map(fn ($row) => [
                'commission_type' => $row->commission_type,
                'amount'          => $row->amount,
                'currency_id'     => $row->currency_id,
            ])->all();
    }

    if (blank($commissionRows)) {
        $commissionRows = [['commission_type' => 'percent', 'amount' => '', 'currency_id' => null]];
    }
@endphp

{{-- ==================== IDENTITY ==================== --}}
<x-ui.form-section title="Identity" icon="bi-person-badge"
                   subtitle="The side decides which tax and bank fields apply below.">
    <div class="form-stack">

        <x-ui.select name="agent_type" label="Agent Type" required horizontal
                     :options="$agentTypes" :selected="$type"
                     :placeholder="false"
                     hint="Supplier and jobber side agents are paid in India; buyer side agents are paid abroad." />

        <x-ui.field name="name" label="Agent Name" :value="$agent?->name" required
                    horizontal maxlength="200" placeholder="E.g. John Doe & Co." />

        {{-- Typed, not generated — the prototype asks for a duplicate warning,
             which only makes sense for a code the user chooses. --}}
        <div class="row form-line">
            <label for="display_code" class="col-sm-4 col-lg-3 col-form-label fw-semibold">
                Display Code <span class="req">*</span>
            </label>
            <div class="col-sm-8 col-lg-9">
                <input type="text" id="display_code" name="display_code" maxlength="5" required
                       value="{{ old('display_code', $agent?->display_code) }}"
                       class="form-control text-uppercase js-unique-check @error('display_code') is-invalid @enderror"
                       data-field="display_code" placeholder="AGT01" autocomplete="off">
                @error('display_code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text js-unique-feedback"></div>
                <div class="form-text">
                    Used in the debit note number —
                    <span class="font-monospace" id="dn-preview">GT/{{ old('display_code', $agent?->display_code) ?: '—' }}/001/25-26</span>
                </div>
            </div>
        </div>

        {{-- Required: the agent dropdowns on the Buyer and Supplier forms are
             filtered by category, so an agent with none is unreachable. --}}
        <div class="row form-line">
            <label for="categories" class="col-sm-4 col-lg-3 col-form-label fw-semibold">
                Categories <span class="req">*</span>
            </label>
            <div class="col-sm-8 col-lg-9">
                <select id="categories" name="categories[]" multiple data-searchable
                        data-placeholder="Select categories…"
                        class="form-select @error('categories') is-invalid @enderror">
                    @foreach($categories as $id => $name)
                        <option value="{{ $id }}"
                            @selected(in_array($id, old('categories', $agent?->categories->pluck('id')->all() ?? [])))>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @error('categories')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text">The product categories this agent handles.</div>
            </div>
        </div>

    </div>
</x-ui.form-section>

{{-- ==================== CONTACT ==================== --}}
<x-ui.form-section title="Contact & Address" icon="bi-geo-alt"
                   subtitle="Prints on the commission debit note.">
    <div class="form-stack">
        <x-ui.field name="phone" label="Phone" :value="$agent?->phone" required
                    horizontal maxlength="30" placeholder="+91 98XXX XXXXX" />

        <x-ui.field name="city" label="City" :value="$agent?->city" required
                    horizontal maxlength="80" placeholder="Mumbai" />

        <x-ui.textarea name="address" label="Full Address" :value="$agent?->address" required
                       horizontal rows="2" maxlength="255"
                       placeholder="Shop / office no., street, area, city, state, pincode" />
    </div>
</x-ui.form-section>

{{-- ==================== TAX — domestic sides only ==================== --}}
<x-ui.form-section title="Tax Details" icon="bi-receipt" id="tax-section"
                   subtitle="Shown on the debit note. Supplier and jobber side only."
                   :class="$domestic ? '' : 'd-none'">
    <div class="form-stack">
        <x-ui.field name="gst_number" label="GST Number" :value="$agent?->gst_number"
                    :required="$domestic" horizontal maxlength="15"
                    placeholder="27AAAAA0000A1Z5"
                    hint="15-character GSTIN. The PAN below must match characters 3–12 of it." />

        <x-ui.field name="pan_number" label="PAN Number" :value="$agent?->pan_number"
                    :required="$domestic" horizontal maxlength="10"
                    placeholder="AAAAA0000A" hint="10-character PAN — used for TDS deduction." />
    </div>
</x-ui.form-section>

{{-- ==================== COMMISSION ==================== --}}
<x-ui.form-section title="Commission" icon="bi-percent"
                   subtitle="Feeds the costing panel, the debit note and the agent commission settlement.">
    <div class="form-stack">

        <x-ui.select name="calculation_basis_id" label="Commission Basis" required horizontal searchable
                     :options="$calculationBases" :selected="$agent?->calculation_basis_id"
                     placeholder="Search commission basis…"
                     hint="What a percentage entry is a percentage of — typically Net Value for a supplier side agent, FOB Value for a buyer side one." />

        {{-- Repeatable entries. One agent may carry several: a percentage on
             value plus a fixed amount per piece is a normal arrangement. --}}
        <div class="row form-line">
            <label class="col-sm-4 col-lg-3 col-form-label fw-semibold">
                Commission Entries <span class="req">*</span>
            </label>
            <div class="col-sm-8 col-lg-9">
                @error('commissions')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width:38%">Type</th>
                                <th style="width:28%">Amount</th>
                                <th style="width:28%" class="js-currency-col @unless($domestic) @else d-none @endunless">Currency</th>
                                <th style="width:6%"></th>
                            </tr>
                        </thead>
                        <tbody id="commission-rows">
                            @foreach($commissionRows as $index => $row)
                                <tr data-commission-row>
                                    <td>
                                        <select name="commissions[{{ $index }}][commission_type]"
                                                class="form-select form-select-sm @error("commissions.{$index}.commission_type") is-invalid @enderror"
                                                aria-label="Commission type">
                                            @foreach($commissionTypes as $value => $label)
                                                <option value="{{ $value }}" @selected(($row['commission_type'] ?? 'percent') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("commissions.{$index}.commission_type")<div class="cell-error">{{ $message }}</div>@enderror
                                    </td>
                                    <td>
                                        <input type="number" step="0.0001" min="0"
                                               name="commissions[{{ $index }}][amount]"
                                               value="{{ $row['amount'] ?? '' }}"
                                               class="form-control form-control-sm @error("commissions.{$index}.amount") is-invalid @enderror"
                                               placeholder="2.5" aria-label="Commission amount">
                                        @error("commissions.{$index}.amount")<div class="cell-error">{{ $message }}</div>@enderror
                                    </td>
                                    <td class="js-currency-col @unless($domestic) @else d-none @endunless">
                                        <select name="commissions[{{ $index }}][currency_id]"
                                                class="form-select form-select-sm @error("commissions.{$index}.currency_id") is-invalid @enderror"
                                                aria-label="Currency">
                                            <option value="">— Select —</option>
                                            @foreach($currencies as $id => $label)
                                                <option value="{{ $id }}" @selected((string) ($row['currency_id'] ?? '') === (string) $id)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error("commissions.{$index}.currency_id")<div class="cell-error">{{ $message }}</div>@enderror
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 js-remove-commission"
                                                aria-label="Remove entry" title="Remove entry">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-commission">
                    <i class="bi bi-plus-lg me-1"></i>Add commission entry
                </button>
                <div class="form-text">Rows with no amount are not saved. Currency applies to buyer side agents only — supplier side commissions are in INR.</div>

                {{-- Cloned by the Add button. In a <template> so nothing inside
                     is picked up by the page-load widget sweep. --}}
                <template id="commission-row-template">
                    <tr data-commission-row>
                        <td>
                            <select name="commissions[__INDEX__][commission_type]" class="form-select form-select-sm" aria-label="Commission type">
                                @foreach($commissionTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.0001" min="0" name="commissions[__INDEX__][amount]"
                                   class="form-control form-control-sm" placeholder="2.5" aria-label="Commission amount">
                        </td>
                        <td class="js-currency-col">
                            <select name="commissions[__INDEX__][currency_id]" class="form-select form-select-sm" aria-label="Currency">
                                <option value="">— Select —</option>
                                @foreach($currencies as $id => $label)
                                    <option value="{{ $id }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 js-remove-commission" aria-label="Remove entry">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                </template>
            </div>
        </div>

        {{-- Which ledger the commission lands in. No default — guessing "we pay"
             would put a payable on our books for an agent the supplier settles. --}}
        <x-ui.select name="commission_paid_by" label="Who Pays This Commission?" required horizontal
                     :options="$commissionPayers" :selected="$agent?->commission_paid_by"
                     placeholder="— Select —"
                     hint="We pay = our payable, raises a debit note. Supplier pays = deducted from the supplier bill. Buyer pays = deducted from the export invoice." />

        <x-ui.select name="payment_term" label="Payment Terms" required horizontal
                     :options="$paymentTerms" :selected="$agent?->payment_term"
                     placeholder="— Select —"
                     hint="When the commission falls due, and therefore when it can be settled." />

        <div id="payment-term-custom-row" class="@unless(old('payment_term', $agent?->payment_term) === 'custom') d-none @endunless">
            <x-ui.field name="payment_term_custom" label="Describe the Term"
                        :value="$agent?->payment_term_custom" horizontal maxlength="255"
                        placeholder="e.g. 50% on shipment, balance on buyer payment" />
        </div>

    </div>
</x-ui.form-section>

{{-- ==================== BANK ==================== --}}
<x-ui.form-section title="Bank Details" icon="bi-bank"
                   subtitle="Where the commission is transferred.">
    <div class="form-stack">
        <x-ui.field name="bank_name" label="Bank Name" :value="$agent?->bank_name" required
                    horizontal maxlength="120" placeholder="e.g. HDFC Bank" />

        <x-ui.field name="account_number" label="Account Number" :value="$agent?->account_number" required
                    horizontal maxlength="40" placeholder="Account number" />

        {{-- One rail or the other, never both. --}}
        <div id="ifsc-row" class="@unless($domestic) d-none @endunless">
            <x-ui.field name="ifsc_code" label="IFSC Code" :value="$agent?->ifsc_code"
                        :required="$domestic" horizontal maxlength="11"
                        placeholder="HDFC0001234" hint="Domestic transfers." />
        </div>

        <div id="swift-row" class="@if($domestic) d-none @endif">
            <x-ui.field name="swift_code" label="SWIFT Code" :value="$agent?->swift_code"
                        :required="! $domestic" horizontal maxlength="11"
                        placeholder="EBILAEAD" hint="International transfers." />
        </div>
    </div>
</x-ui.form-section>

{{-- ==================== STATUS ==================== --}}
<x-ui.form-section title="Status & Remarks" icon="bi-toggle-on">
    <div class="form-stack">
        <x-ui.select name="status" label="Status" required horizontal
                     :options="['active' => 'Active', 'inactive' => 'Inactive']"
                     :selected="$agent?->status ?? 'active'"
                     :placeholder="false"
                     hint="An inactive agent stays on existing orders but drops out of the Buyer and Supplier dropdowns." />

        <x-ui.textarea name="remarks" label="Remarks" :value="$agent?->remarks"
                       horizontal rows="2" placeholder="Internal notes — not printed anywhere" />

        <x-ui.textarea name="comments" label="Comments" :value="$agent?->comments"
                       horizontal rows="2" placeholder="Optional comments" />
    </div>
</x-ui.form-section>

<div class="form-actions">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>{{ $agent ? 'Update' : 'Save' }} Agent
    </button>
    <a href="{{ route('masters.agents.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ------------------------------------------------------------------ *
     * Side-dependent blocks.
     *
     * The server has already rendered the correct state; this only keeps up
     * with a change of type. Fields in a hidden block are cleared, because a
     * buyer-side agent carrying a stale IFSC would be paid on the wrong rail —
     * the form request nulls them too, this is so the user sees it happen.
     * ------------------------------------------------------------------ */
    const typeSelect = document.getElementById('agent_type');

    function toggle(element, visible) {
        if (! element) return;
        element.classList.toggle('d-none', ! visible);

        if (! visible) {
            element.querySelectorAll('input, select').forEach(function (field) {
                if (field.tagName === 'SELECT') {
                    field.tomselect ? field.tomselect.clear(true) : (field.value = '');
                } else {
                    field.value = '';
                }
            });
        }
    }

    function applySide() {
        const domestic = (typeSelect?.value || 'supplier') !== 'buyer';

        toggle(document.getElementById('tax-section'), domestic);
        toggle(document.getElementById('ifsc-row'), domestic);
        toggle(document.getElementById('swift-row'), ! domestic);

        // Currency is a buyer-side concern only; supplier commissions are INR.
        document.querySelectorAll('.js-currency-col').forEach(function (cell) {
            cell.classList.toggle('d-none', domestic);

            if (domestic) {
                const select = cell.querySelector('select');
                if (select) select.value = '';
            }
        });
    }

    typeSelect?.addEventListener('change', applySide);
    applySide();

    /* ------------------------------------------------------------------ *
     * "Custom" payment term opens its description box.
     * ------------------------------------------------------------------ */
    const termSelect = document.getElementById('payment_term');
    const customRow  = document.getElementById('payment-term-custom-row');

    function applyTerm() {
        const custom = termSelect?.value === 'custom';
        customRow?.classList.toggle('d-none', ! custom);

        if (! custom) {
            const input = customRow?.querySelector('input');
            if (input) input.value = '';
        }
    }

    termSelect?.addEventListener('change', applyTerm);
    applyTerm();

    /* ------------------------------------------------------------------ *
     * Commission entries — repeatable rows.
     * ------------------------------------------------------------------ */
    const rows     = document.getElementById('commission-rows');
    const template = document.getElementById('commission-row-template');

    /**
     * Field names carry their row index, so a row added after one was removed
     * must not reuse an index still in the DOM — two inputs sharing
     * commissions[1][amount] would silently drop one on submit.
     */
    function reindex() {
        rows.querySelectorAll('[data-commission-row]').forEach(function (row, index) {
            row.querySelectorAll('[name^="commissions"]').forEach(function (field) {
                field.name = field.name.replace(/commissions\[\d+\]/, 'commissions[' + index + ']');
            });
        });
    }

    rows.addEventListener('click', function (event) {
        const remove = event.target.closest('.js-remove-commission');
        if (! remove) return;

        const all = rows.querySelectorAll('[data-commission-row]');

        // At least one entry is required, so the last row is cleared rather
        // than removed — an empty table would offer nothing but the button.
        if (all.length === 1) {
            all[0].querySelector('input').value = '';
            all[0].querySelectorAll('select').forEach(function (select, i) {
                select.value = i === 0 ? 'percent' : '';
            });
        } else {
            remove.closest('[data-commission-row]').remove();
        }

        reindex();
    });

    document.getElementById('add-commission').addEventListener('click', function () {
        const index = rows.querySelectorAll('[data-commission-row]').length;
        const row   = template.content.cloneNode(true).querySelector('tr');

        row.querySelectorAll('[name*="__INDEX__"]').forEach(function (field) {
            field.name = field.name.replace('__INDEX__', index);
        });

        rows.appendChild(row);
        applySide();          // the new row's currency cell must match the side
        row.querySelector('input')?.focus();
    });

    /* ------------------------------------------------------------------ *
     * "Tell me if a certain code is used already."
     * A convenience only — the unique index is what actually enforces it.
     * ------------------------------------------------------------------ */
    const checkUrl = @json(route('masters.agents.check-code'));
    const ignoreId = @json($agent?->id);
    const original = new Map();

    document.querySelectorAll('.js-unique-check').forEach(function (input) {
        original.set(input, input.value.trim().toUpperCase());
        const feedback = input.parentElement.querySelector('.js-unique-feedback');
        const preview  = document.getElementById('dn-preview');
        let timer = null;

        function clearFeedback() {
            feedback.textContent = '';
            feedback.className = 'form-text js-unique-feedback';
        }

        input.addEventListener('input', function () {
            clearTimeout(timer);
            const value = input.value.trim().toUpperCase();

            if (preview) preview.textContent = 'GT/' + (value || '—') + '/001/25-26';

            if (value === '' || value === original.get(input)) {
                clearFeedback();
                input.classList.remove('is-invalid', 'is-valid');
                return;
            }

            timer = setTimeout(function () {
                const params = new URLSearchParams({ field: input.dataset.field, value: value });
                if (ignoreId) params.append('ignore', ignoreId);

                fetch(checkUrl + '?' + params, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(function (data) {
                        input.classList.toggle('is-invalid', !data.available);
                        input.classList.toggle('is-valid', data.available);
                        feedback.textContent = data.available
                            ? 'Available.'
                            : 'Already taken — choose another.';
                        feedback.className = 'form-text js-unique-feedback ' +
                            (data.available ? 'text-success' : 'text-danger');
                    })
                    .catch(clearFeedback);
            }, 350);
        });
    });
});
</script>
@endpush
