@props(['inquiry' => null])

@php
    $isEdit = (bool) $inquiry;
    $val = fn (string $field, $default = null) => old($field, $isEdit ? $inquiry->{$field} : $default);

    // Computed here rather than inline inside @json() below — Blade's
    // directive-argument parser truncates silently on a long multi-line
    // nested array expression, so the array is built as a plain variable
    // first and @json() is only ever given a simple variable to echo.
    $buyersJs = $buyers->mapWithKeys(function ($b) {
        return [$b->id => [
            'agent_id'               => $b->agent_id,
            'agent_commission_type'  => $b->agent_commission_type,
            'agent_commission_value' => $b->agent_commission_value,
            'currency_id'            => $b->currency_id,
            'categories'             => $b->categories->pluck('id'),
        ]];
    });

    $formatsJs = $formats->mapWithKeys(function ($f) {
        // Standard toggleable columns — enabled/label per this format,
        // falling back to the STANDARD default for a format saved before a
        // column existed. print_only ones (image) never reach a data-entry
        // screen, so they're left out entirely — same filter
        // DocumentFormat::screenColumns() applies.
        $standardColumns = [];
        foreach (\App\Models\DocumentFormatColumn::STANDARD as $key => $meta) {
            if ($meta['print_only']) {
                continue;
            }
            $column = $f->columns->firstWhere('key', $key);
            $standardColumns[$key] = [
                'enabled'     => $column ? (bool) $column->is_enabled : true,
                'label'       => $column->label ?? $meta['label'],
                'mandatory'   => $column ? (bool) $column->is_mandatory : false,
                // Only meaningful on 'size' — the fixed qty-per-tag grid on
                // the item row reads this off the Size column specifically.
                'sub_columns' => $column?->sub_columns ?? [],
            ];
        }

        $customColumns = $f->columns->where('is_custom', true)->where('is_enabled', true)
            ->map(fn ($c) => ['key' => $c->key, 'label' => $c->label])
            ->values();

        return [$f->id => [
            'module'                 => $f->module,
            'allow_multiple_colours' => (bool) $f->allow_multiple_colours,
            'delivery_details'       => $f->delivery_details,
            'packing_details'        => $f->packing_details,
            'units'                  => $f->units->pluck('name'),
            'categories'             => $f->categories->pluck('id'),
            'columns'                => $standardColumns,
            'customColumns'          => $customColumns,
        ]];
    });
@endphp

<input type="hidden" name="mode" id="mode-input" value="{{ old('mode', 'submit') }}">

<x-ui.form-section title="Inquiry Identity" icon="bi-chat-square-text"
                   subtitle="→ Buyer Master · Agent Master · OC on confirmation">
    <div class="row">
        <div class="col-md-3 mb-3">
            <label class="form-label fw-semibold">Inquiry No.</label>
            <input type="text" class="form-control bg-body-tertiary" readonly
                   value="{{ $isEdit ? $inquiry->inquiry_no : $numberPreview.' (auto)' }}">
            <div class="form-text">FY {{ $financialYear }}</div>
        </div>

        <x-ui.field name="inquiry_date" label="Date" type="date" required col="col-md-3"
                    :value="$val('inquiry_date') instanceof \Carbon\CarbonInterface ? $val('inquiry_date')->format('Y-m-d') : $val('inquiry_date', now()->format('Y-m-d'))" />

        <x-ui.field name="buyer_ref" label="Buyer's Ref / Season" col="col-md-3"
                    :value="$val('buyer_ref')" placeholder="e.g. SS-2026" />

        <x-ui.select name="source" label="Source" required col="col-md-3"
                     :options="$sources" :selected="$val('source')" />

        {{-- Change request #8 — free text captured only when Source is "Other". --}}
        <div class="col-md-3 mb-3 {{ $val('source') === 'other' ? '' : 'd-none' }}" id="source-other-row">
            <label for="source_other" class="form-label fw-semibold">Please specify</label>
            <input type="text" id="source_other" name="source_other" maxlength="150"
                   value="{{ $val('source_other') }}"
                   class="form-control @error('source_other') is-invalid @enderror"
                   placeholder="e.g. Trade show referral">
            @error('source_other')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="row">
        <x-ui.select name="buyer_id" label="Buyer" required col="col-md-6"
                     :options="$buyers->pluck('label', 'id')" :selected="$val('buyer_id')" />

        <x-ui.select name="category_id" label="Category" required col="col-md-6"
                     :options="$categories" :selected="$val('category_id')" />
    </div>
</x-ui.form-section>

<x-ui.form-section title="Order Format &amp; Terms" icon="bi-file-earmark-ruled"
                   subtitle="Defines the item table structure across Inquiry → OC → PO.">
    <div class="row">
        <x-ui.select name="document_format_id" label="Order Format" required col="col-md-6"
                     :options="$formats->pluck('name', 'id')" :selected="$val('document_format_id')" />

        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Format Type</label>
            <input type="text" class="form-control bg-body-tertiary" id="format_type" readonly placeholder="— From Format —">
        </div>
    </div>

    <div class="row">
        <x-ui.select name="agent_id" label="Agent" col="col-md-3"
                     :options="$agents" :selected="$val('agent_id')" hint="Buyer Master" />

        <x-ui.select name="agent_commission_type" label="Commission Type" col="col-md-2"
                     :options="['percent' => 'Percent', 'flat' => 'Flat']" :selected="$val('agent_commission_type')" />

        <x-ui.field name="agent_commission_value" label="Commission" type="number" col="col-md-2"
                    :value="$val('agent_commission_value')" placeholder="0.00" />

        <x-ui.select name="currency_id" label="Currency" required col="col-md-2"
                     :options="$currencies" :selected="$val('currency_id')" hint="Buyer Master" />

        <x-ui.field name="exchange_rate" label="Exchange Rate (₹)" type="number" col="col-md-3"
                    :value="$val('exchange_rate')" placeholder="e.g. 88.50" />
    </div>

    <div class="row">
        <x-ui.field name="expected_shipment_date" label="Expected Shipment Date" type="date" col="col-md-4"
                    :value="$val('expected_shipment_date') instanceof \Carbon\CarbonInterface ? $val('expected_shipment_date')->format('Y-m-d') : $val('expected_shipment_date')" />

        <x-ui.field name="remarks" label="Remarks" col="col-md-8"
                    :value="$val('remarks')" placeholder="General remarks…" />
    </div>
</x-ui.form-section>

<x-ui.form-section title="Items, Costing &amp; Follow-ups" icon="bi-table"
                   subtitle="Select a format above to define the table structure. Expand Costing per item for FOB, colour and size breakdown.">
    <div id="items-wrap">
        @php $existingItems = old('items', $isEdit ? $inquiry->items : []); @endphp

        @foreach($existingItems as $item)
            @php
                $isArr = is_array($item);
                $iDesign = $isArr ? ($item['design_no'] ?? '') : $item->design_no;
                $iDesc = $isArr ? ($item['description'] ?? '') : $item->description;
                $iProduct = $isArr ? ($item['product_id'] ?? '') : $item->product_id;
                $iSupplier = $isArr ? ($item['supplier_id'] ?? '') : $item->supplier_id;
                $iUnit = $isArr ? ($item['unit'] ?? '') : $item->unit;
                $iFob = $isArr ? ($item['fob_value_id'] ?? '') : $item->fob_value_id;
                $iPrice = $isArr ? ($item['price'] ?? '') : $item->price;
                $iCostPrice = $isArr ? ($item['cost_price'] ?? '') : $item->cost_price;
                $iStatus = $isArr ? ($item['status'] ?? 'draft') : $item->status;
                $iRemarks = $isArr ? ($item['remarks'] ?? '') : $item->remarks;
                $iColours = $isArr ? ($item['colours'] ?? []) : $item->colours;
                $iProductLabel = $isArr ? null : $item->product?->name;
                $iSupplierLabel = $isArr ? null : $item->supplier?->label;
                $iCustom = $isArr ? ($item['custom'] ?? []) : ($item->custom_values ?? []);
            @endphp
            <div class="inquiry-item border rounded p-3 mb-3" data-item
                 data-product-id="{{ $iProduct }}" data-product-label="{{ $iProductLabel }}"
                 data-supplier-id="{{ $iSupplier }}" data-supplier-label="{{ $iSupplierLabel }}"
                 data-unit="{{ $iUnit }}" data-custom-values="{{ json_encode($iCustom) }}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-semibold item-index-label">Item</span>
                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-item"><i class="bi bi-trash"></i></button>
                </div>
                <div class="row g-2">
                    <div class="col-md-3" data-column="design_no">
                        <label class="form-label small js-column-label">Design No. / Name</label>
                        <input type="text" class="form-control form-control-sm js-field" data-field="design_no" maxlength="150" value="{{ $iDesign }}">
                    </div>
                    <div class="col-md-3" data-column="product">
                        <label class="form-label small js-column-label">Product</label>
                        <select class="form-select form-select-sm js-field js-product-select" data-field="product_id"><option value="">— Select —</option></select>
                    </div>
                    <div class="col-md-3" data-column="supplier">
                        <label class="form-label small js-column-label">Supplier</label>
                        <select class="form-select form-select-sm js-field js-supplier-select" data-field="supplier_id"><option value="">— Select —</option></select>
                    </div>
                    <div class="col-md-2" data-column="unit">
                        <label class="form-label small js-column-label">Unit</label>
                        <select class="form-select form-select-sm js-field js-unit-select" data-field="unit"><option value="">—</option></select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small">Status</label>
                        <select class="form-select form-select-sm js-field" data-field="status">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($iStatus === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <label class="form-label small">Description</label>
                        <input type="text" class="form-control form-control-sm js-field" data-field="description" maxlength="500" value="{{ $iDesc }}">
                    </div>
                    <div class="col-md-2" data-column="price">
                        <label class="form-label small js-column-label">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm js-field js-price" data-field="price" value="{{ $iPrice }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Qty</label>
                        <input type="text" class="form-control form-control-sm js-qty-display" readonly value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Amount</label>
                        <input type="text" class="form-control form-control-sm js-amount-display" readonly value="{{ $isArr ? '0.00' : number_format((float) $item->amount, 2) }}">
                    </div>
                </div>
                <div class="row g-2 mt-1 custom-fields-wrap"></div>
                <button type="button" class="btn btn-sm btn-link px-0 mt-2 js-toggle-costing">
                    <i class="bi bi-caret-down-fill me-1"></i>Costing
                </button>
                <div class="costing-panel d-none mt-2 border-top pt-2">
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small">FOB Value</label>
                            <select class="form-select form-select-sm js-field" data-field="fob_value_id">
                                <option value="">— Select —</option>
                                @foreach($fobValues as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $iFob === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Cost Price / Unit (₹)</label>
                            <input type="number" step="0.01" min="0" class="form-control form-control-sm js-field" data-field="cost_price" value="{{ $iCostPrice }}">
                            <div class="form-text">Internal — feeds Purchase Order, never shown to buyer</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Item Remarks</label>
                            <input type="text" class="form-control form-control-sm js-field" data-field="remarks" maxlength="500" value="{{ $iRemarks }}">
                        </div>
                    </div>
                    <div class="colours-wrap">
                        @foreach($iColours as $colour)
                            @php
                                $cIsArr = is_array($colour);
                                $cName = $cIsArr ? ($colour['colour'] ?? '') : $colour->colour;
                                $cSizes = $cIsArr ? ($colour['sizes'] ?? []) : $colour->sizes;
                            @endphp
                            <div class="inquiry-colour border rounded p-2 mb-2 bg-body-tertiary" data-colour>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="text" class="form-control form-control-sm js-colour-name" placeholder="Colour" maxlength="60" style="max-width:12rem" value="{{ $cName }}">
                                    <span class="text-body-secondary small js-colour-subtotal ms-auto">Qty: 0</span>
                                    <button type="button" class="btn btn-sm btn-outline-danger js-remove-colour"><i class="bi bi-trash"></i></button>
                                </div>
                                <div class="sizes-wrap d-flex flex-wrap gap-2 mb-2">
                                    @foreach($cSizes as $size)
                                        @php
                                            $sIsArr = is_array($size);
                                            $sLabel = $sIsArr ? ($size['size'] ?? '') : $size->size;
                                            $sQty = $sIsArr ? ($size['qty'] ?? 0) : $size->qty;
                                        @endphp
                                        <div class="inquiry-size d-flex align-items-center gap-1" data-size style="max-width:11rem">
                                            <input type="text" class="form-control form-control-sm js-size-label" placeholder="Size" maxlength="20" style="width:5rem" value="{{ $sLabel }}">
                                            <input type="number" min="0" class="form-control form-control-sm js-size-qty" placeholder="Qty" style="width:5rem" value="{{ $sQty }}">
                                            <button type="button" class="btn btn-sm btn-outline-danger js-remove-size"><i class="bi bi-x"></i></button>
                                        </div>
                                    @endforeach
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary js-add-size">
                                    <i class="bi bi-plus-lg me-1"></i>Add size
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary js-add-colour mt-1">
                        <i class="bi bi-plus-lg me-1"></i>Add colour
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    @error('items')
        <div class="invalid-feedback d-block mb-2">{{ $message }}</div>
    @enderror

    <button type="button" id="add-item" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Add Item
    </button>

    <hr class="my-4">

    <h6 class="fw-semibold mb-2">Buyer Follow-up</h6>
    <p class="text-body-secondary small">Track all buyer communication — dates &amp; comments.</p>

    <div id="followups-wrap">
        @php $existingFollowUps = old('followups', $isEdit ? $inquiry->followUps : []); @endphp

        @foreach($existingFollowUps as $followUp)
            @php
                $fIsArr = is_array($followUp);
                $fId = $fIsArr ? ($followUp['id'] ?? '') : $followUp->id;
                $fDate = $fIsArr ? ($followUp['date'] ?? '') : $followUp->follow_up_date?->format('Y-m-d');
                $fComment = $fIsArr ? ($followUp['comment'] ?? '') : $followUp->comment;
            @endphp
            <div class="inquiry-followup d-flex gap-2 mb-2" data-followup data-id="{{ $fId }}">
                <input type="date" class="form-control form-control-sm js-followup-date" style="max-width:11rem" value="{{ $fDate }}">
                <input type="text" class="form-control form-control-sm js-followup-comment" placeholder="Comment…" value="{{ $fComment }}">
                <button type="button" class="btn btn-sm btn-outline-danger js-remove-followup"><i class="bi bi-x"></i></button>
            </div>
        @endforeach
    </div>

    <button type="button" id="add-followup" class="btn btn-sm btn-outline-secondary w-100">
        <i class="bi bi-plus-lg me-1"></i>Add buyer follow-up
    </button>
</x-ui.form-section>

<x-ui.form-section title="Delivery &amp; Packing Details" icon="bi-box-seam"
                   subtitle="Pre-fills from Order Format · editable per inquiry.">
    <div class="row">
        <x-ui.textarea name="delivery_details" label="Delivery Details" required col="col-12"
                       rows="3" :value="$val('delivery_details')" />
    </div>
    <div class="row">
        <x-ui.textarea name="packing_details" label="Packing Details" required col="col-12"
                       rows="3" :value="$val('packing_details')" />
    </div>
</x-ui.form-section>

<x-ui.form-section title="Module Connections" icon="bi-diagram-3"
                   subtitle="This inquiry feeds into the following modules.">
    <div class="d-flex flex-wrap gap-2">
        @foreach(['Buyer Master', 'Agent Master', 'Order Format', 'Order Confirmations (on confirmation)'] as $module)
            <span class="badge text-bg-light border fw-normal">{{ $module }}</span>
        @endforeach
    </div>
</x-ui.form-section>

<div class="form-actions d-flex flex-wrap gap-2 align-items-center">
    <div class="me-auto">
        <label class="form-label small text-body-secondary mb-1">Status</label>
        <select name="status" id="status-select" class="form-select form-select-sm">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected($val('status', 'draft') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <a href="{{ $isEdit ? route('sales.inquiries.show', $inquiry) : route('sales.inquiries.index') }}"
       class="btn btn-outline-secondary">Cancel</a>
    <button type="submit" id="btn-save-draft" class="btn btn-outline-secondary">
        <i class="bi bi-save me-1"></i>Save Draft
    </button>
    <button type="submit" id="btn-submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>Submit
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('inquiry-form');

    /* ------------------------- Reference data from server ------------------------- */

    const buyers = @json($buyersJs);

    const formats = @json($formatsJs);

    const productsUrl  = "{{ route('sales.inquiries.products') }}";
    const suppliersUrl = "{{ route('sales.inquiries.suppliers') }}";

    const buyerSelect    = document.getElementById('buyer_id');
    const categorySelect = document.getElementById('category_id');
    const formatSelect   = document.getElementById('document_format_id');
    const formatTypeEl   = document.getElementById('format_type');
    const deliveryEl     = document.getElementById('delivery_details');
    const packingEl      = document.getElementById('packing_details');
    const itemsWrap      = document.getElementById('items-wrap');

    /* ------------------------------------------------------------------ *
     * Change request #8 — "Please specify" only when Source is "Other".
     * ------------------------------------------------------------------ */
    const sourceSelect  = document.getElementById('source');
    const sourceOtherRow = document.getElementById('source-other-row');
    const sourceOtherInput = document.getElementById('source_other');

    function applySourceOther() {
        const visible = sourceSelect.value === 'other';
        sourceOtherRow.classList.toggle('d-none', ! visible);
        if (! visible) sourceOtherInput.value = '';
    }

    sourceSelect?.addEventListener('change', applySourceOther);

    /* ------------------------------ Cascades ------------------------------ */

    /**
     * Narrows which options show, and — only when clearIfInvalid is true —
     * wipes the selection if it's no longer among them.
     *
     * clearIfInvalid must stay false on the initial page load. An edit
     * screen opens with whatever category/format the record was actually
     * saved with, which may no longer match the *current* Category Master
     * links (the link can be added, removed or simply never configured
     * after the record was saved). Clearing it then would silently blank
     * out a real saved value the moment the page loads, before the user has
     * touched anything. The clear is only correct as the direct consequence
     * of the user themselves changing the parent dropdown — set true from
     * the buyer/category 'change' listeners below, never from init.
     */
    function filterOptionsByCategory(selectEl, allowedIds, clearIfInvalid) {
        Array.from(selectEl.options).forEach(function (opt) {
            if (opt.value === '') return;
            opt.hidden = allowedIds && allowedIds.length ? ! allowedIds.includes(Number(opt.value)) : false;
        });
        if (clearIfInvalid && selectEl.selectedOptions[0] && selectEl.selectedOptions[0].hidden) {
            selectEl.value = '';
        }
    }

    function applyBuyerCategoryFilter(clearIfInvalid) {
        const buyer = buyers[buyerSelect.value];
        filterOptionsByCategory(categorySelect, buyer ? buyer.categories : null, clearIfInvalid);
    }

    function applyCategoryFormatFilter(clearIfInvalid) {
        const categoryId = categorySelect.value ? Number(categorySelect.value) : null;
        Array.from(formatSelect.options).forEach(function (opt) {
            if (opt.value === '') return;
            const meta = formats[opt.value];
            opt.hidden = categoryId && meta ? ! meta.categories.includes(categoryId) : false;
        });
        if (clearIfInvalid && formatSelect.selectedOptions[0] && formatSelect.selectedOptions[0].hidden) {
            formatSelect.value = '';
        }
    }

    function applyFormatMeta() {
        const meta = formats[formatSelect.value];
        formatTypeEl.value = meta ? meta.module : '';
        populateUnitSelects(meta ? meta.units : []);
        itemsWrap.querySelectorAll('.js-add-colour').forEach(function (btn) {
            btn.classList.toggle('d-none', ! (meta && meta.allow_multiple_colours));
        });
        itemsWrap.querySelectorAll(':scope > .inquiry-item').forEach(function (itemEl) {
            applyColumnsToItem(itemEl, meta);
        });
    }

    /**
     * Reflects the selected Order Format's column settings onto one item row
     * — which fields are shown, what they're labelled, the Price column's
     * unit suffix (mirrors DocumentFormat::priceLabel() server-side), colour
     * naming, and any custom columns the format defines. Called both when
     * the format changes and when a new item row is added, so a row built
     * after the format was already picked starts in the right shape too.
     */
    function applyColumnsToItem(itemEl, meta) {
        const columns = (meta && meta.columns) || {};

        ['design_no', 'product', 'supplier', 'unit', 'price'].forEach(function (key) {
            const wrap = itemEl.querySelector('[data-column="' + key + '"]');
            if (! wrap) return;

            const col = columns[key];
            const enabled = col ? col.enabled : true;
            wrap.classList.toggle('d-none', ! enabled);

            const labelEl = wrap.querySelector('.js-column-label');
            if (labelEl && col) {
                labelEl.textContent = key === 'price' && columns.unit
                    ? col.label + (itemEl.querySelector('.js-unit-select').value ? ' / ' + itemEl.querySelector('.js-unit-select').value : '')
                    : col.label;
                if (col.mandatory) labelEl.textContent += ' *';
            }
        });

        // A single colour row always exists to carry the size breakdown even
        // when the format has multi-colour off — only the ability to name it
        // depends on the format's own colour column.
        const colourNamesEnabled = !! (meta && meta.allow_multiple_colours && (! columns.colour || columns.colour.enabled));
        itemEl.querySelectorAll('.js-colour-name').forEach(function (input) {
            input.classList.toggle('d-none', ! colourNamesEnabled);
        });

        itemEl.querySelectorAll(':scope .colours-wrap > .inquiry-colour').forEach(function (colourEl) {
            applySizeGrid(colourEl, meta);
        });

        applyCustomColumns(itemEl, (meta && meta.customColumns) || []);
    }

    /**
     * The Size column's sub-columns (set on the Order Format) turn a colour
     * row's free-form "+ Add size" entry into a fixed grid — one qty box per
     * tag. Reuses the exact same .inquiry-size / .js-size-label / .js-size-qty
     * shape the free-form rows already use, so the submit handler's colour/size
     * compile loop needs no changes at all — it just sees rows either way.
     */
    function sizeTagsFor(meta) {
        const sizeCol = meta && meta.columns && meta.columns.size;
        return (sizeCol && sizeCol.sub_columns) || [];
    }

    function applySizeGrid(colourEl, meta) {
        const tags = sizeTagsFor(meta);
        const sizesWrap = colourEl.querySelector('.sizes-wrap');
        const addSizeBtn = colourEl.querySelector('.js-add-size');

        if (! tags.length) {
            // Free-form mode — leave whatever rows already exist (typed by
            // the user, or loaded from a saved item); just make sure a row
            // built while a grid-format was selected is editable again.
            sizesWrap.querySelectorAll('.inquiry-size').forEach(function (row) {
                row.classList.remove('is-grid');
                row.querySelector('.js-size-label').readOnly = false;
                row.querySelector('.js-remove-size').classList.remove('d-none');
            });
            if (addSizeBtn) addSizeBtn.classList.remove('d-none');
            return;
        }

        // Grid mode — keep any qty already typed against a tag the format
        // still offers, drop rows for tags it no longer offers, add rows for
        // new ones. Keyed by label, same "keep what's there" idea
        // loadSelectOptions() uses elsewhere in this file.
        const existingQty = {};
        sizesWrap.querySelectorAll('.inquiry-size').forEach(function (row) {
            const label = row.querySelector('.js-size-label').value;
            if (label) existingQty[label] = row.querySelector('.js-size-qty').value;
        });

        sizesWrap.innerHTML = '';

        tags.forEach(function (tag) {
            const node = sizeTemplate.content.cloneNode(true);

            const labelInput = node.querySelector('.js-size-label');
            labelInput.value = tag;
            labelInput.readOnly = true;

            node.querySelector('.js-size-qty').value = existingQty[tag] || '';

            const row = node.querySelector('.inquiry-size');
            row.classList.add('is-grid');
            node.querySelector('.js-remove-size').classList.add('d-none');

            sizesWrap.appendChild(node);
        });

        if (addSizeBtn) addSizeBtn.classList.add('d-none');
    }

    /**
     * One text input per custom column the format defines, appended after
     * the standard fields. Rebuilt on every format change — existing typed
     * values for a key are kept if that key is still offered, a saved
     * item's data-custom-values seeds the fields on first load.
     */
    function applyCustomColumns(itemEl, customColumns) {
        const wrap = itemEl.querySelector('.custom-fields-wrap');
        if (! wrap) return;

        const current = {};
        wrap.querySelectorAll('[data-custom-key]').forEach(function (el) {
            current[el.dataset.customKey] = el.querySelector('input').value;
        });

        let saved = {};
        if (itemEl.dataset.customValues) {
            try { saved = JSON.parse(itemEl.dataset.customValues) || {}; } catch (e) { saved = {}; }
        }

        wrap.innerHTML = '';

        customColumns.forEach(function (col) {
            const field = document.createElement('div');
            field.className = 'col-md-3';
            field.dataset.customKey = col.key;

            const label = document.createElement('label');
            label.className = 'form-label small';
            label.textContent = col.label;

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control form-control-sm js-custom-field';
            input.maxLength = 255;
            input.value = current[col.key] !== undefined ? current[col.key] : (saved[col.key] || '');

            field.appendChild(label);
            field.appendChild(input);
            wrap.appendChild(field);
        });
    }

    function populateUnitSelects(units) {
        itemsWrap.querySelectorAll('.js-unit-select').forEach(function (select) {
            const current = select.dataset.selected || select.value;
            select.innerHTML = '<option value="">—</option>';
            units.forEach(function (unit) {
                const opt = document.createElement('option');
                opt.value = unit;
                opt.textContent = unit;
                if (unit === current) opt.selected = true;
                select.appendChild(opt);
            });
        });
    }

    function refreshItemProductsAndSuppliers() {
        const categoryId = categorySelect.value || '';
        itemsWrap.querySelectorAll('.inquiry-item').forEach(function (itemEl) {
            loadSelectOptions(itemEl.querySelector('.js-product-select'), productsUrl, categoryId);
            loadSelectOptions(itemEl.querySelector('.js-supplier-select'), suppliersUrl, categoryId);
        });
    }

    function loadSelectOptions(selectEl, url, categoryId, presetValue, presetLabel) {
        const selected = presetValue !== undefined ? presetValue : selectEl.dataset.selected;

        fetch(url + '?category_id=' + encodeURIComponent(categoryId || ''))
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                selectEl.innerHTML = '<option value="">— Select —</option>';

                let found = false;
                rows.forEach(function (row) {
                    const opt = document.createElement('option');
                    opt.value = row.id;
                    opt.textContent = row.text;
                    if (selected && String(row.id) === String(selected)) { opt.selected = true; found = true; }
                    selectEl.appendChild(opt);
                });

                // Saved value no longer in the category's list (category
                // changed after the item was saved) — keep it selectable so
                // the row still shows what was actually saved.
                if (selected && ! found) {
                    const opt = document.createElement('option');
                    opt.value = selected;
                    opt.textContent = (presetLabel !== undefined ? presetLabel : selectEl.dataset.selectedLabel) || ('#' + selected);
                    opt.selected = true;
                    selectEl.appendChild(opt);
                }
            });
    }

    buyerSelect.addEventListener('change', function () {
        const buyer = buyers[buyerSelect.value];
        applyBuyerCategoryFilter(true);

        if (buyer) {
            document.getElementById('agent_id').value = buyer.agent_id || '';
            document.getElementById('agent_commission_type').value = buyer.agent_commission_type || '';
            document.getElementById('agent_commission_value').value = buyer.agent_commission_value || '';
            document.getElementById('currency_id').value = buyer.currency_id || '';
        }
    });

    categorySelect.addEventListener('change', function () {
        applyCategoryFormatFilter(true);
        refreshItemProductsAndSuppliers();
    });

    formatSelect.addEventListener('change', function () {
        applyFormatMeta();

        // Only overwrite delivery/packing when still blank — a manual edit
        // on re-selecting the format must not be clobbered.
        const meta = formats[formatSelect.value];
        if (meta) {
            if (! deliveryEl.value.trim()) deliveryEl.value = meta.delivery_details || '';
            if (! packingEl.value.trim()) packingEl.value = meta.packing_details || '';
        }
    });

    /* ------------------------------ Item rows ------------------------------ */

    const itemTemplate = document.getElementById('tpl-item');
    const colourTemplate = document.getElementById('tpl-colour');
    const sizeTemplate = document.getElementById('tpl-size');

    function renumberItems() {
        itemsWrap.querySelectorAll('.inquiry-item').forEach(function (el, index) {
            el.querySelector('.item-index-label').textContent = 'Item #' + (index + 1);
        });
    }

    function recalcItem(itemEl) {
        let qty = 0;
        itemEl.querySelectorAll('.inquiry-colour').forEach(function (colourEl) {
            let colourQty = 0;
            colourEl.querySelectorAll('.js-size-qty').forEach(function (input) {
                colourQty += parseInt(input.value || '0', 10) || 0;
            });
            colourEl.querySelector('.js-colour-subtotal').textContent = 'Qty: ' + colourQty;
            qty += colourQty;
        });

        const price = parseFloat(itemEl.querySelector('.js-price').value || '0') || 0;
        itemEl.querySelector('.js-qty-display').value = qty;
        itemEl.querySelector('.js-amount-display').value = (qty * price).toFixed(2);
    }

    function addColour(itemEl) {
        const node = colourTemplate.content.cloneNode(true);
        const coloursWrap = itemEl.querySelector('.colours-wrap');
        coloursWrap.appendChild(node);
        applySizeGrid(coloursWrap.lastElementChild, formats[formatSelect.value]);
        recalcItem(itemEl);
    }

    function addSize(colourEl) {
        const node = sizeTemplate.content.cloneNode(true);
        colourEl.querySelector('.sizes-wrap').appendChild(node);
    }

    function initItem(itemEl) {
        const categoryId = categorySelect.value || '';

        loadSelectOptions(
            itemEl.querySelector('.js-product-select'), productsUrl, categoryId,
            itemEl.dataset.productId || '', itemEl.dataset.productLabel || ''
        );
        loadSelectOptions(
            itemEl.querySelector('.js-supplier-select'), suppliersUrl, categoryId,
            itemEl.dataset.supplierId || '', itemEl.dataset.supplierLabel || ''
        );

        const unitSelect = itemEl.querySelector('.js-unit-select');
        unitSelect.dataset.selected = itemEl.dataset.unit || '';

        const meta = formats[formatSelect.value];
        populateUnitSelects(meta ? meta.units : []);
        applyColumnsToItem(itemEl, meta);

        if (itemEl.querySelectorAll('.inquiry-colour').length === 0) {
            addColour(itemEl);
        }

        recalcItem(itemEl);
    }

    document.getElementById('add-item').addEventListener('click', function () {
        const node = itemTemplate.content.cloneNode(true);
        itemsWrap.appendChild(node);
        const itemEl = itemsWrap.lastElementChild;
        initItem(itemEl);
        renumberItems();
    });

    // Delegated: covers rows rendered by Blade on load and rows added later.
    itemsWrap.addEventListener('click', function (e) {
        if (e.target.closest('.js-remove-item')) {
            e.target.closest('.inquiry-item').remove();
            renumberItems();
            return;
        }
        if (e.target.closest('.js-toggle-costing')) {
            const panel = e.target.closest('.inquiry-item').querySelector('.costing-panel');
            panel.classList.toggle('d-none');
            return;
        }
        if (e.target.closest('.js-add-colour')) {
            addColour(e.target.closest('.inquiry-item'));
            return;
        }
        if (e.target.closest('.js-remove-colour')) {
            const itemEl = e.target.closest('.inquiry-item');
            e.target.closest('.inquiry-colour').remove();
            recalcItem(itemEl);
            return;
        }
        if (e.target.closest('.js-add-size')) {
            addSize(e.target.closest('.inquiry-colour'));
            return;
        }
        if (e.target.closest('.js-remove-size')) {
            const itemEl = e.target.closest('.inquiry-item');
            e.target.closest('.inquiry-size').remove();
            recalcItem(itemEl);
            return;
        }
    });

    itemsWrap.addEventListener('input', function (e) {
        if (e.target.classList.contains('js-size-qty') || e.target.classList.contains('js-price')) {
            recalcItem(e.target.closest('.inquiry-item'));
        }
    });

    // Price column header reads "Price / <unit>" — refresh it when the
    // row's own unit changes, same live label DocumentFormat::priceLabel()
    // computes server-side for the format's own preview.
    itemsWrap.addEventListener('change', function (e) {
        if (e.target.classList.contains('js-unit-select')) {
            applyColumnsToItem(e.target.closest('.inquiry-item'), formats[formatSelect.value]);
        }
    });

    /* ---------------------------- Buyer follow-ups ---------------------------- */

    const followUpsWrap = document.getElementById('followups-wrap');
    const followUpTemplate = document.getElementById('tpl-followup');

    document.getElementById('add-followup').addEventListener('click', function () {
        const node = followUpTemplate.content.cloneNode(true);
        followUpsWrap.appendChild(node);
    });

    followUpsWrap.addEventListener('click', function (e) {
        if (e.target.closest('.js-remove-followup')) {
            e.target.closest('.inquiry-followup').remove();
        }
    });

    /* --------------------------- Mode / status buttons --------------------------- */

    document.getElementById('btn-save-draft').addEventListener('click', function () {
        document.getElementById('mode-input').value = 'draft';
        document.getElementById('status-select').value = 'draft';
    });

    document.getElementById('btn-submit').addEventListener('click', function () {
        document.getElementById('mode-input').value = 'submit';
    });

    /* ------------------------- Compile the grid on submit ------------------------- */

    function appendHidden(name, value) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value == null ? '' : value;
        input.dataset.generated = '1';
        form.appendChild(input);
    }

    form.addEventListener('submit', function () {
        form.querySelectorAll('input[data-generated]').forEach(function (el) { el.remove(); });

        itemsWrap.querySelectorAll(':scope > .inquiry-item').forEach(function (itemEl, i) {
            const field = (name) => itemEl.querySelector('[data-field="' + name + '"]').value;

            appendHidden('items[' + i + '][design_no]', field('design_no'));
            appendHidden('items[' + i + '][description]', field('description'));
            appendHidden('items[' + i + '][product_id]', field('product_id'));
            appendHidden('items[' + i + '][supplier_id]', field('supplier_id'));
            appendHidden('items[' + i + '][unit]', field('unit'));
            appendHidden('items[' + i + '][fob_value_id]', field('fob_value_id'));
            appendHidden('items[' + i + '][price]', field('price'));
            appendHidden('items[' + i + '][cost_price]', field('cost_price'));
            appendHidden('items[' + i + '][status]', field('status'));
            appendHidden('items[' + i + '][remarks]', field('remarks'));

            itemEl.querySelectorAll(':scope .colours-wrap > .inquiry-colour').forEach(function (colourEl, j) {
                appendHidden('items[' + i + '][colours][' + j + '][colour]', colourEl.querySelector('.js-colour-name').value);

                colourEl.querySelectorAll(':scope .sizes-wrap > .inquiry-size').forEach(function (sizeEl, k) {
                    appendHidden('items[' + i + '][colours][' + j + '][sizes][' + k + '][size]', sizeEl.querySelector('.js-size-label').value);
                    appendHidden('items[' + i + '][colours][' + j + '][sizes][' + k + '][qty]', sizeEl.querySelector('.js-size-qty').value || 0);
                });
            });

            itemEl.querySelectorAll(':scope .custom-fields-wrap [data-custom-key]').forEach(function (fieldEl) {
                appendHidden('items[' + i + '][custom][' + fieldEl.dataset.customKey + ']', fieldEl.querySelector('input').value);
            });
        });

        followUpsWrap.querySelectorAll(':scope > .inquiry-followup').forEach(function (fEl, i) {
            if (fEl.dataset.id) appendHidden('followups[' + i + '][id]', fEl.dataset.id);
            appendHidden('followups[' + i + '][date]', fEl.querySelector('.js-followup-date').value);
            appendHidden('followups[' + i + '][comment]', fEl.querySelector('.js-followup-comment').value);
        });
    });

    /* --------------------------------- Init --------------------------------- */

    // false: narrow the option lists to match, but never blank out a value
    // the record actually has saved just because it was opened.
    applyBuyerCategoryFilter(false);
    applyCategoryFormatFilter(false);
    applyFormatMeta();
    itemsWrap.querySelectorAll(':scope > .inquiry-item').forEach(initItem);
});
</script>
@endpush

<template id="tpl-item">
    <div class="inquiry-item border rounded p-3 mb-3" data-item>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-semibold item-index-label">Item</span>
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-item"><i class="bi bi-trash"></i></button>
        </div>
        <div class="row g-2">
            <div class="col-md-3" data-column="design_no">
                <label class="form-label small js-column-label">Design No. / Name</label>
                <input type="text" class="form-control form-control-sm js-field" data-field="design_no" maxlength="150">
            </div>
            <div class="col-md-3" data-column="product">
                <label class="form-label small js-column-label">Product</label>
                <select class="form-select form-select-sm js-field js-product-select" data-field="product_id"><option value="">— Select —</option></select>
            </div>
            <div class="col-md-3" data-column="supplier">
                <label class="form-label small js-column-label">Supplier</label>
                <select class="form-select form-select-sm js-field js-supplier-select" data-field="supplier_id"><option value="">— Select —</option></select>
            </div>
            <div class="col-md-2" data-column="unit">
                <label class="form-label small js-column-label">Unit</label>
                <select class="form-select form-select-sm js-field js-unit-select" data-field="unit"><option value="">—</option></select>
            </div>
            <div class="col-md-1">
                <label class="form-label small">Status</label>
                <select class="form-select form-select-sm js-field" data-field="status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row g-2 mt-1">
            <div class="col-md-6">
                <label class="form-label small">Description</label>
                <input type="text" class="form-control form-control-sm js-field" data-field="description" maxlength="500">
            </div>
            <div class="col-md-2" data-column="price">
                <label class="form-label small js-column-label">Price</label>
                <input type="number" step="0.01" min="0" class="form-control form-control-sm js-field js-price" data-field="price">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Qty</label>
                <input type="text" class="form-control form-control-sm js-qty-display" readonly value="0">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Amount</label>
                <input type="text" class="form-control form-control-sm js-amount-display" readonly value="0.00">
            </div>
        </div>
        <div class="row g-2 mt-1 custom-fields-wrap"></div>
        <button type="button" class="btn btn-sm btn-link px-0 mt-2 js-toggle-costing">
            <i class="bi bi-caret-down-fill me-1"></i>Costing
        </button>
        <div class="costing-panel d-none mt-2 border-top pt-2">
            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label small">FOB Value</label>
                    <select class="form-select form-select-sm js-field" data-field="fob_value_id">
                        <option value="">— Select —</option>
                        @foreach($fobValues as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Cost Price / Unit (₹)</label>
                    <input type="number" step="0.01" min="0" class="form-control form-control-sm js-field" data-field="cost_price">
                    <div class="form-text">Internal — feeds Purchase Order, never shown to buyer</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Item Remarks</label>
                    <input type="text" class="form-control form-control-sm js-field" data-field="remarks" maxlength="500">
                </div>
            </div>
            <div class="colours-wrap"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary js-add-colour mt-1">
                <i class="bi bi-plus-lg me-1"></i>Add colour
            </button>
        </div>
    </div>
</template>

<template id="tpl-colour">
    <div class="inquiry-colour border rounded p-2 mb-2 bg-body-tertiary" data-colour>
        <div class="d-flex align-items-center gap-2 mb-2">
            <input type="text" class="form-control form-control-sm js-colour-name" placeholder="Colour" maxlength="60" style="max-width:12rem">
            <span class="text-body-secondary small js-colour-subtotal ms-auto">Qty: 0</span>
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-colour"><i class="bi bi-trash"></i></button>
        </div>
        <div class="sizes-wrap d-flex flex-wrap gap-2 mb-2"></div>
        <button type="button" class="btn btn-sm btn-outline-secondary js-add-size">
            <i class="bi bi-plus-lg me-1"></i>Add size
        </button>
    </div>
</template>

<template id="tpl-size">
    <div class="inquiry-size d-flex align-items-center gap-1" data-size style="max-width:11rem">
        <input type="text" class="form-control form-control-sm js-size-label" placeholder="Size" maxlength="20" style="width:5rem">
        <input type="number" min="0" class="form-control form-control-sm js-size-qty" placeholder="Qty" style="width:5rem">
        <button type="button" class="btn btn-sm btn-outline-danger js-remove-size"><i class="bi bi-x"></i></button>
    </div>
</template>

<template id="tpl-followup">
    <div class="inquiry-followup d-flex gap-2 mb-2" data-followup>
        <input type="date" class="form-control form-control-sm js-followup-date" style="max-width:11rem">
        <input type="text" class="form-control form-control-sm js-followup-comment" placeholder="Comment…">
        <button type="button" class="btn btn-sm btn-outline-danger js-remove-followup"><i class="bi bi-x"></i></button>
    </div>
</template>
