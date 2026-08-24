@php
    $sizes = \App\Models\ProductionOrder::SIZES;
    $stages = \App\Models\ProductionOrder::STAGE_KEYS;
    $order = $order ?? null;
    $inputName = $inputName ?? 'sizes';
@endphp

<div class="table-responsive js-stage-grid-wrap">
    <table class="table table-sm table-bordered align-middle mb-0 size-qty-grid">
        <thead class="table-light">
            <tr>
                <th style="min-width: 9rem">Stage</th>
                @foreach ($sizes as $size)
                    <th class="text-center" style="width: 4.5rem">{{ $size }}</th>
                @endforeach
                <th class="text-center" style="width: 5rem">Total</th>
                <th class="text-center" style="width: 6.5rem">Total Damage</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($stages as $key => $meta)
                @php
                    $rowTotal = 0;
                    foreach ($sizes as $size) {
                        $rowTotal += (int) old("{$inputName}.{$key}.{$size}", $order?->sizeQty($key, $size) ?? 0);
                    }
                    $rowDamage = (int) old("damage.{$key}", $order?->stageDamage($key) ?? 0);
                @endphp
                <tr data-stage-row data-stage-key="{{ $key }}" data-stage-label="{{ $meta['label'] }}">
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
                    <td>
                        <input type="number" min="0" step="1"
                               name="damage[{{ $key }}]"
                               class="form-control form-control-sm text-center js-stage-damage"
                               value="{{ $rowDamage }}">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="form-text mb-0 mt-2">S–5XL is good pcs at that stage. <strong>Total Damage</strong> is pieces lost there (not size-wise). Next stage cannot exceed previous good pcs (total − damage).</p>
<div class="alert alert-danger py-2 small mt-2 mb-0 d-none js-stage-flow-error" role="alert"></div>

@once
<script>
    function garmentStageGridRows(wrap) {
        return Array.from(wrap.querySelectorAll('[data-stage-row]'));
    }

    function garmentStageRowTotal(row) {
        var sum = 0;
        row.querySelectorAll('.js-size-qty').forEach(function (input) {
            sum += parseInt(input.value, 10) || 0;
        });
        return sum;
    }

    function garmentStageRowDamage(row) {
        var input = row.querySelector('.js-stage-damage');
        return input ? (parseInt(input.value, 10) || 0) : 0;
    }

    function garmentValidateStageFlow(wrap) {
        var errorBox = wrap.querySelector('.js-stage-flow-error');
        var rows = garmentStageGridRows(wrap);
        var messages = [];
        var orderQtyInput = wrap.closest('form') && wrap.closest('form').querySelector('[name="total_qty"]');
        var prevGood = orderQtyInput ? (parseInt(orderQtyInput.value, 10) || 0) : Infinity;
        var prevLabel = 'order qty';

        rows.forEach(function (row) {
            var total = garmentStageRowTotal(row);
            var damage = garmentStageRowDamage(row);
            var label = row.getAttribute('data-stage-label') || 'Stage';
            var totalCell = row.querySelector('.js-size-row-total');
            if (totalCell) totalCell.textContent = total.toLocaleString();

            row.querySelectorAll('.js-size-qty, .js-stage-damage').forEach(function (el) {
                el.classList.remove('is-invalid');
            });

            if (total === 0 && damage === 0) {
                return;
            }

            if (damage > total) {
                messages.push(label + ' damage (' + damage + ') cannot exceed ' + label + ' qty (' + total + ').');
                var dmg = row.querySelector('.js-stage-damage');
                if (dmg) dmg.classList.add('is-invalid');
            }

            if (total > prevGood) {
                messages.push(label + ' qty (' + total + ') cannot exceed ' + prevLabel + ' good pcs (' + prevGood + '). Damaged pieces cannot move forward.');
                row.querySelectorAll('.js-size-qty').forEach(function (el) {
                    el.classList.add('is-invalid');
                });
            }

            prevGood = Math.max(0, total - damage);
            prevLabel = label;
        });

        if (errorBox) {
            if (messages.length) {
                errorBox.textContent = messages[0];
                errorBox.classList.remove('d-none');
            } else {
                errorBox.textContent = '';
                errorBox.classList.add('d-none');
            }
        }

        return messages.length === 0;
    }

    document.addEventListener('input', function (e) {
        if (!e.target.classList.contains('js-size-qty') && !e.target.classList.contains('js-stage-damage')) return;
        var wrap = e.target.closest('.js-stage-grid-wrap');
        if (wrap) garmentValidateStageFlow(wrap);
    });

    document.addEventListener('submit', function (e) {
        var wrap = e.target.querySelector('.js-stage-grid-wrap');
        if (!wrap) return;
        if (!garmentValidateStageFlow(wrap)) {
            e.preventDefault();
            wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
</script>
@endonce
