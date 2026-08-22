@php
    $sizes = \App\Models\ProductionOrder::SIZES;
    $stages = \App\Models\ProductionOrder::STAGE_KEYS;
    $order = $order ?? null;
    $inputName = $inputName ?? 'sizes';
@endphp

<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0 size-qty-grid">
        <thead class="table-light">
            <tr>
                <th style="min-width: 9rem">Stage</th>
                @foreach ($sizes as $size)
                    <th class="text-center" style="width: 4.5rem">{{ $size }}</th>
                @endforeach
                <th class="text-center" style="width: 5rem">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stages as $key => $meta)
                @php
                    $rowTotal = 0;
                    foreach ($sizes as $size) {
                        $rowTotal += (int) old("{$inputName}.{$key}.{$size}", $order?->sizeQty($key, $size) ?? 0);
                    }
                @endphp
                <tr data-stage-row>
                    <td class="fw-semibold small">{{ $meta['label'] }}</td>
                    @foreach ($sizes as $size)
                        <td>
                            <input type="number" min="0" step="1"
                                   name="{{ $inputName }}[{{ $key }}][{{ $size }}]"
                                   class="form-control form-control-sm text-center js-size-qty"
                                   value="{{ old("{$inputName}.{$key}.{$size}", $order?->sizeQty($key, $size) ?? 0) }}">
                        </td>
                    @endforeach
                    <td class="text-center fw-bold js-size-row-total">{{ number_format($rowTotal) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="form-text mb-0 mt-2">S–5XL per stage (same layout as a job-work delivery challan). Row total updates as you type and is saved as that stage’s pcs.</p>

@once
<script>
    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('js-size-qty')) return;
        var row = e.target.closest('[data-stage-row]');
        if (!row) return;
        var sum = 0;
        row.querySelectorAll('.js-size-qty').forEach(function (input) {
            sum += parseInt(input.value, 10) || 0;
        });
        var totalCell = row.querySelector('.js-size-row-total');
        if (totalCell) totalCell.textContent = sum.toLocaleString();
    });
</script>
@endonce
