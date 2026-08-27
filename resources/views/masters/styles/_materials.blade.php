@php
    $products = $products ?? collect();
    $style = $style ?? null;
    $rows = old('materials', $style?->materials?->map(fn ($m) => [
        'product_id' => $m->product_id,
        'qty_per_pc' => $m->qty_per_pc,
        'unit' => $m->unit,
    ])->all() ?? [['product_id' => '', 'qty_per_pc' => '', 'unit' => '']]);
    if ($rows === []) {
        $rows = [['product_id' => '', 'qty_per_pc' => '', 'unit' => '']];
    }
@endphp

<div class="p-3 bg-body-tertiary border rounded mb-4" id="style-materials">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Fabric &amp; accessories BOM</h6>
            <p class="small text-body-secondary mb-0">Same style on a new order will show this stock first. Qty is per garment.</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-material-row">Add material</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Item (fabric / accessory)</th>
                    <th style="width:8rem">Qty / pc</th>
                    <th style="width:7rem">Unit</th>
                    <th style="width:7rem">In stock</th>
                    <th style="width:3rem"></th>
                </tr>
            </thead>
            <tbody id="material-rows">
                @foreach ($rows as $i => $row)
                    @php $pid = $row['product_id'] ?? ''; @endphp
                    <tr>
                        <td>
                            <select name="materials[{{ $i }}][product_id]" class="form-select form-select-sm js-material-product">
                                <option value="">— Select item —</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}"
                                            data-unit="{{ $product->unit_po }}"
                                            data-stock="{{ $product->qty_on_hand }}"
                                            @selected((string) $pid === (string) $product->id)>
                                        {{ $product->name }} ({{ \App\Models\Product::KINDS[$product->item_kind] ?? $product->item_kind }})
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="number" step="0.0001" min="0" name="materials[{{ $i }}][qty_per_pc]" class="form-control form-control-sm" value="{{ $row['qty_per_pc'] ?? '' }}"></td>
                        <td><input type="text" name="materials[{{ $i }}][unit]" class="form-control form-control-sm js-material-unit" value="{{ $row['unit'] ?? '' }}"></td>
                        <td class="small text-body-secondary js-material-stock">
                            @php $p = $products->firstWhere('id', (int) $pid); @endphp
                            {{ $p ? number_format((float) $p->qty_on_hand, 3) : '—' }}
                        </td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger js-remove-material">&times;</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
    (function () {
        var tbody = document.getElementById('material-rows');
        if (!tbody) return;
        var addBtn = document.getElementById('add-material-row');
        function nextIndex() { return tbody.querySelectorAll('tr').length; }
        function bindRow(row) {
            var sel = row.querySelector('.js-material-product');
            if (!sel) return;
            sel.addEventListener('change', function () {
                var opt = sel.options[sel.selectedIndex];
                row.querySelector('.js-material-unit').value = opt.getAttribute('data-unit') || '';
                row.querySelector('.js-material-stock').textContent = opt.getAttribute('data-stock') || '—';
            });
            row.querySelector('.js-remove-material').addEventListener('click', function () {
                if (tbody.querySelectorAll('tr').length > 1) row.remove();
            });
        }
        tbody.querySelectorAll('tr').forEach(bindRow);
        addBtn.addEventListener('click', function () {
            var i = nextIndex();
            var html = tbody.querySelector('tr').outerHTML.replace(/materials\[\d+]/g, 'materials[' + i + ']');
            tbody.insertAdjacentHTML('beforeend', html);
            var row = tbody.querySelector('tr:last-child');
            row.querySelectorAll('input').forEach(function (el) { el.value = ''; });
            row.querySelector('select').selectedIndex = 0;
            row.querySelector('.js-material-stock').textContent = '—';
            bindRow(row);
        });
    })();
</script>
