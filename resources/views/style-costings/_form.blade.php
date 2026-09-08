@props(['costing' => null, 'selectedStyle' => null, 'lines' => []])

@php
    $val = fn (string $field, $default = null) => old($field, $costing?->{$field} ?? $default);
    $date = $val('costing_date');
    if ($date instanceof \Carbon\CarbonInterface) {
        $date = $date->format('Y-m-d');
    }
    $oldLines = old('lines');
    $rows = is_array($oldLines) && $oldLines !== [] ? $oldLines : $lines;
    $selectedId = old('garment_style_id', $costing?->garment_style_id ?? $selectedStyle?->id);
@endphp

<x-ui.form-section title="Style costing" icon="bi-calculator"
                   subtitle="BOM qty × rate = material. Cut-make and other sit on top. Approve to sign the rupee cost of this style.">
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Costing No.</label>
            <input type="text" class="form-control bg-body-tertiary" readonly
                   value="{{ $costing?->costing_num ?? 'Auto on save' }}">
        </div>
        <x-ui.field name="costing_date" label="Date" type="date" required col="col-md-4"
                    :value="$date ?? now()->format('Y-m-d')" />
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold required">Garment Style</label>
            @if($costing)
                <input type="hidden" name="garment_style_id" value="{{ $costing->garment_style_id }}">
                <input type="text" class="form-control bg-body-tertiary" readonly
                       value="{{ $costing->garmentStyle?->style_number }} — {{ $costing->garmentStyle?->name }}">
            @else
                <select name="garment_style_id" id="garment_style_id"
                        class="form-select @error('garment_style_id') is-invalid @enderror" required
                        onchange="if (this.value) { window.location = '{{ route('style-costings.create') }}?style_id=' + this.value; }">
                    <option value="">— Select —</option>
                    @foreach($styles as $style)
                        <option value="{{ $style->id }}" @selected((string) $selectedId === (string) $style->id)>
                            {{ $style->style_number }} — {{ $style->name }}
                            @if($style->buyer) ({{ $style->buyer->company_name }}) @endif
                        </option>
                    @endforeach
                </select>
            @endif
            @error('garment_style_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            @if(! $costing && ! $selectedStyle)
                <div class="form-text">Pick a style to load its BOM. Then fill ₹ rates.</div>
            @endif
        </div>
    </div>

    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered align-middle mb-0" id="costing-lines">
            <thead class="table-light">
                <tr>
                    <th>Material (from BOM)</th>
                    <th>Type</th>
                    <th class="text-end" style="width:7rem">Qty / pc</th>
                    <th style="width:5rem">Unit</th>
                    <th class="text-end" style="width:8rem">Rate ₹</th>
                    <th class="text-end" style="width:8rem">Amount ₹</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $i => $row)
                    @php
                        $qty = (float) ($row['qty_per_pc'] ?? 0);
                        $rate = (float) ($row['rate'] ?? 0);
                    @endphp
                    <tr>
                        <td>
                            <input type="hidden" name="lines[{{ $i }}][product_id]" value="{{ $row['product_id'] ?? '' }}">
                            <input type="hidden" name="lines[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}">
                            <input type="hidden" name="lines[{{ $i }}][item_kind]" value="{{ $row['item_kind'] ?? '' }}">
                            {{ $row['description'] ?? '—' }}
                        </td>
                        <td>{{ \App\Models\Product::KINDS[$row['item_kind'] ?? ''] ?? ($row['item_kind'] ?? '—') }}</td>
                        <td>
                            <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end js-qty"
                                   name="lines[{{ $i }}][qty_per_pc]" value="{{ $qty }}">
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm"
                                   name="lines[{{ $i }}][unit]" value="{{ $row['unit'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" step="0.0001" min="0" class="form-control form-control-sm text-end js-rate"
                                   name="lines[{{ $i }}][rate]" value="{{ $rate }}">
                        </td>
                        <td class="text-end fw-semibold js-amount">{{ number_format($qty * $rate, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-body-secondary py-4">
                            No BOM lines. Add materials on the style, or enter cut-make below.
                            @if($selectedStyle)
                                <a href="{{ route('masters.styles.edit', $selectedStyle) }}">Edit style BOM</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="row">
        <x-ui.field name="cm_cost" label="Cut-make / CM ₹ per pc" type="number" col="col-md-4"
                    :value="$val('cm_cost', 0)" class="js-cm" step="0.0001" min="0"
                    hint="Stitching / CM charge for one piece." />
        <x-ui.field name="other_cost" label="Other ₹ per pc" type="number" col="col-md-4"
                    :value="$val('other_cost', 0)" class="js-other" step="0.0001" min="0"
                    hint="Overhead, packing, wash, etc." />
        <div class="col-md-4 mb-3">
            <label class="form-label fw-semibold">Total cost / pc</label>
            <div class="form-control bg-body-tertiary fw-bold" id="costing-total">₹ 0.00</div>
        </div>
    </div>

    <div class="row">
        <x-ui.textarea name="notes" label="Notes" col="col-12" rows="2" :value="$val('notes')"
                       placeholder="Rate from last PO, pending trim quote, etc." />
    </div>
</x-ui.form-section>

@push('scripts')
<script>
    (function () {
        const table = document.getElementById('costing-lines');
        const totalEl = document.getElementById('costing-total');
        const cmEl = document.getElementById('cm_cost');
        const otherEl = document.getElementById('other_cost');
        if (!table || !totalEl) return;

        const recalc = () => {
            let material = 0;
            table.querySelectorAll('tbody tr').forEach((tr) => {
                const qty = parseFloat(tr.querySelector('.js-qty')?.value || '0') || 0;
                const rate = parseFloat(tr.querySelector('.js-rate')?.value || '0') || 0;
                const amount = qty * rate;
                material += amount;
                const cell = tr.querySelector('.js-amount');
                if (cell) cell.textContent = amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
            const cm = parseFloat(cmEl?.value || '0') || 0;
            const other = parseFloat(otherEl?.value || '0') || 0;
            totalEl.textContent = '₹ ' + (material + cm + other).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        table.addEventListener('input', recalc);
        cmEl?.addEventListener('input', recalc);
        otherEl?.addEventListener('input', recalc);
        recalc();
    })();
</script>
@endpush
