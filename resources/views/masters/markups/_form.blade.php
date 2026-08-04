@props([
    'markup'    => null,
    'suppliers' => [],
    'buyers'    => [],
    'discounts' => [],
])

{{--
    Markup Master.

    Laid out as the client's prototype lays it out: the two parties, the
    auto-stamped record date, then the pricing rules with the three formula
    lines printed underneath so the arithmetic is visible while it is typed.
--}}

<x-ui.form-section title="Parties" icon="bi-arrow-left-right"
                   subtitle="A markup rule answers one question: what do we charge this buyer for this supplier's goods.">
    <div class="row">
        <x-ui.select name="supplier_id" label="Supplier Name" required searchable
                     :options="$suppliers" :selected="$markup?->supplier_id"
                     placeholder="— Select Supplier —" />

        <x-ui.select name="buyer_id" label="Buyer Name" required searchable
                     :options="$buyers" :selected="$markup?->buyer_id"
                     placeholder="— Select Buyer —" />
    </div>
</x-ui.form-section>

<x-ui.form-section title="Record Date" icon="bi-calendar3"
                   subtitle="Stamped by the system when the rule is first saved.">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-semibold">Date</label>
            <div class="input-group">
                <span class="input-group-text bg-body-secondary"><i class="bi bi-calendar3"></i></span>
                {{-- Read-only and not submitted. The service stamps it, and
                     record_date is not fillable, so a crafted POST cannot
                     backdate a rule. --}}
                <input type="text" class="form-control"
                       value="{{ $markup?->record_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}" readonly>
            </div>
            <div class="form-text">
                {{ $markup
                    ? 'When this rule was written. Editing it does not move the date.'
                    : 'Auto-generated on record creation.' }}
            </div>
        </div>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Pricing Rules" icon="bi-percent"
                   subtitle="Applied per cost price at OC / PO entry.">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="markup_percent" class="form-label fw-semibold">
                Markup % <span class="req">*</span>
            </label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" max="999.99"
                       id="markup_percent" name="markup_percent"
                       class="form-control @error('markup_percent') is-invalid @enderror"
                       value="{{ old('markup_percent', $markup?->markup_percent) }}"
                       placeholder="e.g. 10" required>
                <span class="input-group-text">%</span>
            </div>
            @error('markup_percent')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
            <div class="form-text">Added on top of cost price &rarr; client price.</div>
        </div>

        {{-- Read-only: it belongs to the supplier. Filled server-side on load
             and refreshed by the cascade endpoint when the supplier changes,
             so the profit line below never shows a stale number. --}}
        <div class="col-md-6 mb-3">
            <label for="discount_display" class="form-label fw-semibold">Discount %</label>
            <div class="input-group">
                <input type="text" id="discount_display" class="form-control"
                       value="{{ $markup?->supplier ? number_format($markup->discountPercent(), 2) : '' }}"
                       placeholder="— select a supplier —" readonly
                       data-url="{{ route('masters.markups.supplier-discount') }}">
                <span class="input-group-text">%</span>
            </div>
            <div class="form-text">
                Auto-filled from Supplier Master &middot;
                <a href="{{ route('masters.suppliers.index') }}">edit there to change</a>.
            </div>
        </div>
    </div>

    {{-- The three lines from the prototype, worked live against a sample cost
         price. Rendering the numbers as well as the formulae is the point:
         "10%" means nothing until you see what it does to 100. --}}
    <div class="formula-strip">
        <div class="formula-cell">
            <div class="formula-head">Formula</div>
            <div class="formula-body">CP + Markup% = Client Price</div>
            <div class="formula-value" id="calc-client">—</div>
        </div>
        <div class="formula-cell">
            <div class="formula-head">Formula</div>
            <div class="formula-body">CP − Discount% = Our Cost</div>
            <div class="formula-value" id="calc-cost">—</div>
        </div>
        <div class="formula-cell">
            <div class="formula-head">Profit</div>
            <div class="formula-body">Client Price − Our Cost</div>
            <div class="formula-value text-success" id="calc-profit">—</div>
        </div>
        <div class="formula-note">
            Worked against a sample cost price of
            <input type="number" id="sample-cp" class="form-control form-control-sm d-inline-block"
                   style="width:6rem" value="100" min="0" step="1">
        </div>
    </div>
</x-ui.form-section>

<x-ui.form-section title="Status &amp; Notes" icon="bi-toggles">
    <div class="row">
        <x-ui.select name="status" label="Status" required
                     :options="['active' => 'Active', 'inactive' => 'Inactive']"
                     :selected="$markup?->status ?? 'active'"
                     :placeholder="false" />
    </div>
    <div class="row">
        <x-ui.textarea name="remarks" label="Remarks" :value="$markup?->remarks"
                       rows="2" col="col-12" placeholder="Optional notes" />
    </div>
</x-ui.form-section>

{{-- Sheet-style footnote: which screens will read this record. Kept because
     the prototype shows it and it answers "why am I filling this in". --}}
<x-ui.form-section title="Integration Points" icon="bi-diagram-3"
                   subtitle="This record will be referenced by the following modules.">
    <div class="d-flex flex-wrap gap-2">
        @foreach(['Order Confirmations', 'Purchase Orders', 'Export Documents', 'Buyer Receipts', 'Reports'] as $module)
            <span class="badge text-bg-light border fw-normal">{{ $module }}</span>
        @endforeach
    </div>
</x-ui.form-section>

<div class="form-actions">
    <button type="submit" class="btn btn-primary px-4">
        <i class="bi bi-check-lg me-1"></i>{{ $markup ? 'Update' : 'Save' }} Markup Rule
    </button>
    <a href="{{ route('masters.markups.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const supplier = document.getElementById('supplier_id');
    const markup   = document.getElementById('markup_percent');
    const discount = document.getElementById('discount_display');
    const sample   = document.getElementById('sample-cp');

    if (! supplier || ! markup || ! discount || ! sample) return;

    // Every supplier's discount, so the first paint and most changes need no
    // round trip. The endpoint is the fallback for a supplier added since the
    // page was rendered.
    const known = @json($discounts);

    const money = (n) => '₹' + n.toLocaleString('en-IN', {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
    });

    function recalc() {
        const cp = parseFloat(sample.value);
        const m  = parseFloat(markup.value);
        const d  = parseFloat(discount.value);

        const cells = {
            'calc-client': Number.isFinite(cp) && Number.isFinite(m) ? cp * (1 + m / 100) : null,
            'calc-cost':   Number.isFinite(cp) ? cp * (1 - (Number.isFinite(d) ? d : 0) / 100) : null,
        };

        cells['calc-profit'] = cells['calc-client'] !== null && cells['calc-cost'] !== null
            // Rounded halves, then subtracted — matches Markup::profit(), so
            // the preview and the stored arithmetic cannot disagree.
            ? Math.round(cells['calc-client'] * 100) / 100 - Math.round(cells['calc-cost'] * 100) / 100
            : null;

        Object.entries(cells).forEach(([id, value]) => {
            document.getElementById(id).textContent = value === null ? '—' : money(value);
        });
    }

    function applyDiscount(value) {
        discount.value = (value === null || value === undefined) ? '' : Number(value).toFixed(2);
        recalc();
    }

    supplier.addEventListener('change', function () {
        const id = supplier.value;

        if (! id) return applyDiscount(null);

        if (Object.prototype.hasOwnProperty.call(known, id)) {
            return applyDiscount(known[id]);
        }

        const url = new URL(discount.dataset.url, window.location.origin);
        url.searchParams.set('supplier_id', id);

        fetch(url, { headers: { Accept: 'application/json' } })
            .then((r) => (r.ok ? r.json() : Promise.reject(r.status)))
            .then((data) => applyDiscount(data.discount_percent))
            // A failed lookup must not leave a stale discount sitting under a
            // new supplier — that would show a profit that is not real.
            .catch(() => applyDiscount(null));
    });

    [markup, sample].forEach((el) => el.addEventListener('input', recalc));
    recalc();
});
</script>
@endpush
