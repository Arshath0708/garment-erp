@php
    $planRows = $planRows ?? [];
    $orderId = isset($order) ? $order->id : null;
@endphp
<div class="p-3 bg-body-tertiary border rounded mb-4" id="order-material-plan">
    <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-boxes me-1"></i> Material for this order</h6>
    <p class="small text-body-secondary mb-2">Same fabric/accessories already in stock can be used first. Set <strong>Use stock</strong> to 0 to buy new. Buy qty = required − use stock.</p>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Material</th>
                    <th>Type</th>
                    <th class="text-end">Per pc</th>
                    <th class="text-end">Required</th>
                    <th class="text-end">In stock</th>
                    <th class="text-end">Use stock</th>
                    <th class="text-end">Buy new</th>
                </tr>
            </thead>
            <tbody id="plan-body">
                @forelse ($planRows as $i => $row)
                    <tr>
                        <td>
                            {{ $row['name'] }}
                            @if(! empty($row['size_range']) && $row['size_range'] !== 'All sizes')
                                <div class="small text-body-secondary">{{ $row['size_range'] }}</div>
                            @endif
                            <input type="hidden" name="materials[{{ $i }}][product_id]" value="{{ $row['product_id'] }}">
                        </td>
                        <td><span class="badge text-bg-light border">{{ $row['kind_label'] }}</span></td>
                        <td class="text-end">{{ number_format($row['qty_per_pc'], 4) }} {{ $row['unit'] }}</td>
                        <td class="text-end fw-semibold js-required">{{ number_format($row['required_qty'], 3) }}</td>
                        <td class="text-end {{ $row['qty_on_hand'] + 0 < $row['required_qty'] ? 'text-danger' : 'text-success' }}">{{ number_format($row['qty_on_hand'], 3) }}</td>
                        <td style="width:7.5rem">
                            <input type="number" step="0.001" min="0" max="{{ $row['qty_on_hand'] }}"
                                   name="materials[{{ $i }}][use_stock_qty]"
                                   class="form-control form-control-sm text-end js-use-stock"
                                   value="{{ old('materials.'.$i.'.use_stock_qty', $row['use_stock_qty']) }}">
                        </td>
                        <td class="text-end fw-bold js-buy">{{ number_format($row['buy_qty'], 3) }}</td>
                    </tr>
                @empty
                    <tr class="js-empty-plan"><td colspan="7" class="text-body-secondary small">Pick a style with a BOM to see stock vs buy.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<script>
    (function () {
        var wrap = document.getElementById('order-material-plan');
        if (!wrap) return;
        var body = document.getElementById('plan-body');
        var styleEl = wrap.closest('form').querySelector('[name="garment_style_id"]');
        var qtyEl = wrap.closest('form').querySelector('[name="total_qty"]');
        var url = @json(route('manufacturing.material-plan'));
        var orderId = @json($orderId);

        function recalc(row) {
            var req = parseFloat(row.querySelector('.js-required').textContent.replace(/,/g, '')) || 0;
            var use = parseFloat(row.querySelector('.js-use-stock').value) || 0;
            var buy = Math.max(0, Math.round((req - use) * 1000) / 1000);
            row.querySelector('.js-buy').textContent = buy.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 3 });
        }
        body.addEventListener('input', function (e) {
            if (e.target.classList.contains('js-use-stock')) recalc(e.target.closest('tr'));
        });

        function render(rows) {
            if (!rows.length) {
                body.innerHTML = '<tr class="js-empty-plan"><td colspan="7" class="text-body-secondary small">No BOM on this style. Add fabric/accessories on Style Master.</td></tr>';
                return;
            }
            body.innerHTML = rows.map(function (row, i) {
                var low = row.qty_on_hand < row.required_qty ? 'text-danger' : 'text-success';
                return '<tr>' +
                    '<td>' + row.name + '<input type="hidden" name="materials[' + i + '][product_id]" value="' + row.product_id + '"></td>' +
                    '<td><span class="badge text-bg-light border">' + (row.kind_label || '') + '</span></td>' +
                    '<td class="text-end">' + row.qty_per_pc + ' ' + (row.unit || '') + '</td>' +
                    '<td class="text-end fw-semibold js-required">' + row.required_qty + '</td>' +
                    '<td class="text-end ' + low + '">' + row.qty_on_hand + '</td>' +
                    '<td><input type="number" step="0.001" min="0" max="' + row.qty_on_hand + '" name="materials[' + i + '][use_stock_qty]" class="form-control form-control-sm text-end js-use-stock" value="' + row.use_stock_qty + '"></td>' +
                    '<td class="text-end fw-bold js-buy">' + row.buy_qty + '</td>' +
                    '</tr>';
            }).join('');
        }

        function load() {
            if (!styleEl || !styleEl.value || !qtyEl || !qtyEl.value) return;
            var qs = 'garment_style_id=' + encodeURIComponent(styleEl.value) + '&total_qty=' + encodeURIComponent(qtyEl.value);
            if (orderId) qs += '&order_id=' + orderId;
            fetch(url + '?' + qs, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { render(data.rows || []); });
        }
        if (styleEl) styleEl.addEventListener('change', load);
        if (qtyEl) qtyEl.addEventListener('change', load);
        if (!body.querySelector('.js-use-stock')) load();
    })();
</script>
