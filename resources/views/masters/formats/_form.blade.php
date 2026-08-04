@props([
    'format'  => null,
    'columns' => [],
    'units'   => [],
])

@php
    use App\Models\DocumentFormatColumn;

    // old() wins so a failed validation round-trip keeps what the user had.
    $columnState = old('columns', collect($columns)->map(fn ($c) => [
        'label'   => $c['label'],
        'enabled' => $c['enabled'] ? '1' : null,
    ])->all());

    $unitList = old('units', $units);

    $customList = old('custom_columns', $format
        ? $format->columns->where('is_custom', true)->pluck('label')->values()->all()
        : []);
@endphp

{{--
    Order Format master — "Define the column structure for this purchase order
    format".

    Section order follows the client's prototype: identity, units, columns,
    live preview, delivery/packing, then the module connections footnote.
--}}

<x-ui.form-section title="Format Identity" icon="bi-file-earmark-ruled"
                   subtitle="Linked to categories from the Category Master; used by Purchase Orders.">
    <div class="row">
        <x-ui.field name="name" label="Format Name" :value="$format?->name" required
                    col="col-12" placeholder="e.g. Format 4 — Colour × Size Grid" />
    </div>
    <div class="row">
        <x-ui.textarea name="description" label="Description / Notes" :value="$format?->description"
                       rows="2" col="col-12"
                       placeholder="Which categories use this format, any special notes…" />
    </div>
    <div class="row">
        <x-ui.select name="status" label="Status" required
                     :options="['active' => 'Active', 'inactive' => 'Inactive']"
                     :selected="$format?->status ?? 'active'"
                     :placeholder="false" />

        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Multiple colour rows per item</label>
            <div class="form-check form-switch mt-1">
                {{-- Hidden field first: an unchecked switch posts nothing, and
                     the request requires the key. --}}
                <input type="hidden" name="allow_multiple_colours" value="0">
                <input class="form-check-input" type="checkbox" role="switch"
                       id="allow_multiple_colours" name="allow_multiple_colours" value="1"
                       @checked(old('allow_multiple_colours', $format?->allow_multiple_colours ?? false))>
                <label class="form-check-label" for="allow_multiple_colours" id="colour-switch-label">
                    Off — one colour per item row
                </label>
            </div>
            <div class="form-text">
                When on, each item row in Inquiry / OC / PO gets a <strong>+ Add colour</strong> button.
                All colour rows share the same Design, Product, Supplier, FOB and size breakdown.
            </div>
        </div>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Units" icon="bi-rulers"
                   subtitle="Shared across PO, OC, Item Summary, Export Docs, Debit Note and Agent Commission.">
    <p class="text-body-secondary small">
        The selected unit in a PO row auto-populates the label in the Price column header
        (e.g. <span class="font-monospace">Price / PCS</span>).
    </p>

    <div class="unit-chips" id="unit-chips">
        @foreach($unitList as $unit)
            <span class="unit-chip">
                {{ $unit }}
                <input type="hidden" name="units[]" value="{{ $unit }}">
                <button type="button" class="unit-chip-remove" aria-label="Remove {{ $unit }}">&times;</button>
            </span>
        @endforeach
    </div>

    <div class="d-flex gap-2 mt-2" style="max-width:26rem">
        {{-- Not a <form> control that submits: pressing Enter here must add a
             chip, not save the format. --}}
        <input type="text" id="unit-input" class="form-control form-control-sm"
               placeholder="Add unit (e.g. DOZEN, BOX, KGS)" maxlength="20" autocomplete="off">
        <button type="button" id="unit-add" class="btn btn-sm btn-secondary text-nowrap">
            <i class="bi bi-plus-lg me-1"></i>Add Unit
        </button>
    </div>

    @error('units')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error('units.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</x-ui.form-section>

<x-ui.form-section title="Item Table Columns" icon="bi-layout-three-columns"
                   subtitle="Untick a column to drop it from this format. Rename any of them.">
    <p class="text-body-secondary small">
        Sr. No., Qty and Amount are always drawn — a row without them is not an order line.
    </p>

    <div class="table-responsive">
        <table class="table table-sm align-middle grid-table mb-0">
            <thead>
                <tr>
                    <th style="width:90px">Include</th>
                    <th style="width:200px">Column</th>
                    <th>Label on the document</th>
                    <th style="width:110px">Visibility</th>
                </tr>
            </thead>
            <tbody>
                @foreach(DocumentFormatColumn::STANDARD as $key => $meta)
                    <tr>
                        <td>
                            <input type="hidden" name="columns[{{ $key }}][enabled]" value="">
                            <input class="form-check-input js-column-toggle" type="checkbox"
                                   id="col-{{ $key }}"
                                   name="columns[{{ $key }}][enabled]" value="1"
                                   data-key="{{ $key }}"
                                   @checked(filled($columnState[$key]['enabled'] ?? null))>
                        </td>
                        <td><label for="col-{{ $key }}" class="scheme-name mb-0">{{ $meta['label'] }}</label></td>
                        <td>
                            <input type="text" class="form-control form-control-sm js-column-label"
                                   name="columns[{{ $key }}][label]"
                                   data-key="{{ $key }}"
                                   value="{{ $columnState[$key]['label'] ?? $meta['label'] }}"
                                   maxlength="60">
                            @error("columns.{$key}.label")
                                <div class="cell-error">{{ $message }}</div>
                            @enderror
                        </td>
                        <td class="small text-body-secondary">
                            {{ $meta['print_only'] ? 'Print / PDF only' : 'Screen + print' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @error('columns')
        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
    @enderror

    <div class="mt-3">
        <label class="form-label fw-semibold small text-uppercase text-body-secondary"
               style="letter-spacing:.05em">Custom columns</label>

        <div id="custom-columns">
            @foreach($customList as $index => $label)
                <div class="input-group input-group-sm mb-2 custom-column-row" style="max-width:32rem">
                    <input type="text" class="form-control js-custom-label"
                           name="custom_columns[{{ $index }}]" value="{{ $label }}"
                           maxlength="60" placeholder="Column name">
                    <button type="button" class="btn btn-outline-danger js-remove-custom">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>

        <template id="custom-column-template">
            <div class="input-group input-group-sm mb-2 custom-column-row" style="max-width:32rem">
                <input type="text" class="form-control js-custom-label"
                       name="custom_columns[__INDEX__]" maxlength="60" placeholder="Column name">
                <button type="button" class="btn btn-outline-danger js-remove-custom">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </template>

        <button type="button" id="add-custom-column" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus-lg me-1"></i>Add custom column
        </button>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Live Table Preview" icon="bi-table"
                   subtitle="The exact table structure as it appears in Inquiry → OC → PO.">
    <p class="text-body-secondary small">
        Sample data. The image column is hidden on screen and visible on print / PDF only.
    </p>

    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" id="format-preview">
            <thead class="table-light"><tr></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Delivery &amp; Packing Details" icon="bi-box-seam"
                   subtitle="Printed at the bottom of every PO using this format. Pre-fills when a PO is created — editable per PO.">
    <div class="row">
        <x-ui.textarea name="delivery_details" label="Delivery Details" :value="$format?->delivery_details"
                       rows="3" col="col-12"
                       placeholder="e.g. Delivery within 30 days from PO date. Goods to be dispatched to the address below." />
    </div>
    <div class="row">
        <x-ui.textarea name="packing_details" label="Packing Details" :value="$format?->packing_details"
                       rows="3" col="col-12"
                       placeholder="e.g. Each piece in individual poly bag. Assorted packing in export cartons of 12/24 pcs." />
    </div>

    <div class="mt-2">
        <label class="form-label fw-semibold">
            Reference Images
            <span class="text-body-secondary fw-normal small">— shown on print / PDF / Excel export only</span>
        </label>

        {{-- Already saved. Unticking one removes it on save; the file itself is
             deleted by the service, not left behind on disk. --}}
        @if($format && $format->images->isNotEmpty())
            <div class="d-flex flex-wrap gap-3 mb-3">
                @foreach($format->images as $image)
                    <label class="reference-image">
                        <img src="{{ $image->url }}" alt="{{ $image->original_name }}">
                        <span class="reference-image-keep">
                            <input type="checkbox" name="keep_images[]" value="{{ $image->id }}" checked>
                            Keep
                        </span>
                    </label>
                @endforeach
            </div>
        @elseif($format)
            {{-- Posted empty so the service knows the field was offered and an
                 unticked-everything save really means "remove them all". --}}
            <input type="hidden" name="keep_images[]" value="">
        @endif

        <input type="file" name="images[]" id="images" multiple
               accept="image/jpeg,image/png,image/webp"
               class="form-control @error('images.*') is-invalid @enderror">
        <div class="form-text">
            Packing diagrams &middot; label samples &middot; marking references &middot; carton markings.
            JPG, PNG or WebP, up to 4 MB each.
        </div>
        @error('images.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>
</x-ui.form-section>

<x-ui.form-section title="Module Connections" icon="bi-diagram-3"
                   subtitle="This format feeds into the following modules.">
    <div class="d-flex flex-wrap gap-2">
        @foreach(['Inquiries', 'Order Confirmations', 'Purchase Orders', 'Export Documents', 'Debit Notes'] as $module)
            <span class="badge text-bg-light border fw-normal">{{ $module }}</span>
        @endforeach
    </div>
    <div class="form-text mt-2">
        The unit list defined here is shared across all modules above.
        Category linking is managed from the
        @can('category.view')
            <a href="{{ route('masters.categories.index') }}">Category Master</a>.
        @else
            Category Master.
        @endcan
    </div>
</x-ui.form-section>

<div class="form-actions">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>{{ $format ? 'Update' : 'Save' }} Format
    </button>
    <a href="{{ route('masters.formats.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ------------------------------ Units ------------------------------ */

    const chips = document.getElementById('unit-chips');
    const unitInput = document.getElementById('unit-input');
    const unitAdd = document.getElementById('unit-add');

    function currentUnits() {
        return Array.from(chips.querySelectorAll('input[name="units[]"]')).map((i) => i.value);
    }

    function addUnit() {
        // Upper-cased here as well as in the request, so the duplicate check
        // below compares what will actually be stored.
        const value = unitInput.value.trim().toUpperCase();

        if (! value || currentUnits().includes(value)) {
            unitInput.value = '';
            return;
        }

        const chip = document.createElement('span');
        chip.className = 'unit-chip';
        chip.textContent = value;

        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'units[]';
        hidden.value = value;

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'unit-chip-remove';
        remove.setAttribute('aria-label', 'Remove ' + value);
        remove.innerHTML = '&times;';

        chip.append(hidden, remove);
        chips.append(chip);

        unitInput.value = '';
        renderPreview();
    }

    unitAdd.addEventListener('click', addUnit);
    unitInput.addEventListener('keydown', function (e) {
        // Enter in a bare text input submits the form otherwise.
        if (e.key === 'Enter') { e.preventDefault(); addUnit(); }
    });

    chips.addEventListener('click', function (e) {
        const button = e.target.closest('.unit-chip-remove');
        if (! button) return;
        button.closest('.unit-chip').remove();
        renderPreview();
    });

    /* -------------------------- Custom columns -------------------------- */

    const customWrap = document.getElementById('custom-columns');
    const customTemplate = document.getElementById('custom-column-template');

    document.getElementById('add-custom-column').addEventListener('click', function () {
        const html = customTemplate.innerHTML.replace(/__INDEX__/g, String(customWrap.children.length));
        customWrap.insertAdjacentHTML('beforeend', html);
        renderPreview();
    });

    customWrap.addEventListener('click', function (e) {
        if (! e.target.closest('.js-remove-custom')) return;
        e.target.closest('.custom-column-row').remove();
        reindexCustom();
        renderPreview();
    });

    customWrap.addEventListener('input', renderPreview);

    /**
     * Names must stay a dense 0..n-1 sequence: removing a middle row otherwise
     * leaves a gap, and the validator reports errors against indexes the form
     * no longer has.
     */
    function reindexCustom() {
        Array.from(customWrap.children).forEach(function (row, index) {
            const input = row.querySelector('.js-custom-label');
            if (input) input.name = 'custom_columns[' + index + ']';
        });
    }

    /* --------------------------- Colour switch --------------------------- */

    const colourSwitch = document.getElementById('allow_multiple_colours');
    const colourLabel = document.getElementById('colour-switch-label');

    function describeColour() {
        colourLabel.textContent = colourSwitch.checked
            ? 'On — several colour rows per item'
            : 'Off — one colour per item row';
    }

    colourSwitch.addEventListener('change', describeColour);
    describeColour();

    /* ---------------------------- Live preview ----------------------------

       Rebuilt from the form's own state rather than from a saved format, so
       the preview answers "what will this look like" while it is being
       decided, not after. Mirrors DocumentFormat::priceLabel() for the Price
       header. */

    const preview = document.getElementById('format-preview');
    const headRow = preview.querySelector('thead tr');
    const body = preview.querySelector('tbody');

    const SAMPLE = [
        { design_no: 'NS-101', product: 'LADIES EMBROIDERED KURTI', qty: 120, price: 450 },
        { design_no: 'NS-102', product: 'GENTS COTTON SHIRT',       qty: 80,  price: 350 },
    ];

    function money(n) {
        return '₹' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function renderPreview() {
        const unit = currentUnits()[0] || null;

        const cols = [{ key: 'sr_no', label: 'Sr. No.', printOnly: false }];

        document.querySelectorAll('.js-column-toggle').forEach(function (toggle) {
            if (! toggle.checked) return;

            const key = toggle.dataset.key;
            const labelInput = document.querySelector('.js-column-label[data-key="' + key + '"]');
            let label = (labelInput.value || '').trim() || key;

            if (key === 'price' && unit) label += ' / ' + unit;

            cols.push({ key: key, label: label, printOnly: key === 'image' });

            // Qty always sits immediately before Price, as the prototype draws
            // it — it is not switchable, so it has no toggle of its own.
            if (key === 'unit') cols.push({ key: 'qty', label: 'Qty', printOnly: false });
        });

        Array.from(customWrap.querySelectorAll('.js-custom-label')).forEach(function (input) {
            const label = input.value.trim();
            if (label) cols.push({ key: 'custom', label: label, printOnly: false });
        });

        cols.push({ key: 'amount', label: 'Amount', printOnly: false });

        headRow.replaceChildren(...cols.map(function (col) {
            const th = document.createElement('th');
            th.textContent = col.label;
            if (col.printOnly) {
                th.className = 'text-body-secondary fst-italic';
                th.title = 'Hidden on screen — printed on PDF / Excel only';
            }
            return th;
        }));

        body.replaceChildren(...SAMPLE.map(function (row, i) {
            const tr = document.createElement('tr');

            cols.forEach(function (col) {
                const td = document.createElement('td');

                switch (col.key) {
                    case 'sr_no':  td.textContent = String(i + 1); break;
                    case 'design_no': td.textContent = row.design_no; break;
                    case 'product':   td.textContent = row.product; break;
                    case 'qty':       td.textContent = String(row.qty); break;
                    case 'unit':      td.textContent = unit || '—'; break;
                    case 'price':     td.textContent = money(row.price); break;
                    case 'amount':    td.textContent = money(row.qty * row.price); break;
                    case 'image':     td.innerHTML = '<i class="bi bi-image text-body-secondary"></i>'; break;
                    default:          td.textContent = '—';
                }

                if (col.printOnly) td.className = 'text-center';
                tr.append(td);
            });

            return tr;
        }));
    }

    document.querySelectorAll('.js-column-toggle, .js-column-label').forEach(function (el) {
        el.addEventListener(el.type === 'checkbox' ? 'change' : 'input', renderPreview);
    });

    renderPreview();
});
</script>
@endpush
