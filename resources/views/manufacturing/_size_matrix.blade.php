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
                <th style="min-width: 12rem">Stage</th>
                @foreach ($sizes as $size)
                    <th class="text-center" style="width: 4.5rem">{{ $size }}</th>
                @endforeach
                <th class="text-center" style="width: 5rem">Total</th>
                <th class="text-center" style="width: 6.5rem">Total Damage</th>
                <th class="text-center" style="width: 6rem">Action</th>
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
                    <td class="fw-semibold small">
                        <div>{{ $meta['label'] }}</div>
                        <span class="badge bg-secondary bg-opacity-25 text-body small">
                            Balance: {{ number_format($order?->stageWipBalance($key) ?? 0) }} pcs
                        </span>
                    </td>
                    @foreach ($sizes as $size)
                        <td>
                            <input type="number" min="0" step="1"
                                   name="{{ $inputName }}[{{ $key }}][{{ $size }}]"
                                   class="form-control form-control-sm text-center js-size-qty"
                                   data-size="{{ $size }}"
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
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 js-autofill-stage" data-stage="{{ $key }}" title="Copy remaining balance from previous stage">
                            <i class="bi bi-arrow-down-short"></i> Fill
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="form-text mb-0 mt-2">Select <strong>Current Active Stage</strong> first, then enter that stage’s sizes. Later stages stay locked (Printing selected → Stitching cannot be typed). Each size cannot exceed the same size in the previous stage. <strong>Total Damage</strong> is one number per stage.</p>
<div class="alert alert-danger py-2 small mt-2 mb-0 d-none js-stage-flow-error" role="alert" style="white-space: pre-line"></div>

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

    function garmentSelectedStageKey(form) {
        var sel = form && form.querySelector('[name="current_stage"]');
        if (!sel) return 'cutting';
        var v = sel.value || '';
        if (v === 'Printing' || v === 'Printing / Embroidery') return 'printing';
        if (v === 'Stitching') return 'stitching';
        if (v === 'Finishing') return 'finishing';
        if (v === 'Quality Check') return 'qc_passed';
        if (v === 'Packing') return 'packing';
        if (v === 'Dispatch') return 'dispatch';
        return 'cutting';
    }

    function garmentApplyStageLock(wrap) {
        var selected = garmentSelectedStageKey(wrap.closest('form'));
        var pastSelected = false;
        garmentStageGridRows(wrap).forEach(function (row) {
            var lock = pastSelected;
            if (row.getAttribute('data-stage-key') === selected) {
                pastSelected = true;
            }
            row.querySelectorAll('.js-size-qty, .js-stage-damage').forEach(function (input) {
                input.readOnly = lock;
                input.classList.toggle('bg-body-secondary', lock);
                input.title = lock
                    ? 'Select this stage as Current Active Stage first, then enter quantities.'
                    : '';
            });
            row.classList.toggle('opacity-75', lock);
        });
    }

    function garmentRowSnapshot(row) {
        var now = [];
        row.querySelectorAll('.js-size-qty').forEach(function (input) {
            now.push(String(parseInt(input.value, 10) || 0));
        });
        var dmg = row.querySelector('.js-stage-damage');
        now.push(String(dmg ? (parseInt(dmg.value, 10) || 0) : 0));
        return now.join(',');
    }

    function garmentValidateStageFlow(wrap) {
        var errorBox = wrap.querySelector('.js-stage-flow-error');
        var rows = garmentStageGridRows(wrap);
        var messages = [];
        var orderQtyInput = wrap.closest('form') && wrap.closest('form').querySelector('[name="total_qty"]');
        var prevGood = orderQtyInput ? (parseInt(orderQtyInput.value, 10) || 0) : Infinity;
        var prevLabel = 'order qty';
        var prevSizes = {};

        garmentApplyStageLock(wrap);

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

            row.querySelectorAll('.js-size-qty').forEach(function (input) {
                var size = input.getAttribute('data-size');
                var qty = parseInt(input.value, 10) || 0;
                if (prevSizes[size] !== undefined && qty > prevSizes[size]) {
                    messages.push(label + ' ' + size + ' (' + qty + ') cannot exceed ' + prevLabel + ' ' + size + ' (' + prevSizes[size] + ').');
                    input.classList.add('is-invalid');
                }
            });

            prevGood = Math.max(0, total - damage);
            prevLabel = label;
            row.querySelectorAll('.js-size-qty').forEach(function (input) {
                prevSizes[input.getAttribute('data-size')] = parseInt(input.value, 10) || 0;
            });
        });

        if (errorBox) {
            if (messages.length) {
                errorBox.textContent = messages.join('\n');
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

    document.addEventListener('change', function (e) {
        if (!e.target.matches('[name="current_stage"]')) return;
        var form = e.target.closest('form');
        var wrap = form && form.querySelector('.js-stage-grid-wrap');
        if (wrap) garmentValidateStageFlow(wrap);
    });

    document.addEventListener('submit', function (e) {
        var wrap = e.target.querySelector('.js-stage-grid-wrap');
        if (!wrap) return;
        var selected = garmentSelectedStageKey(e.target);
        var pastSelected = false;
        var blocked = [];
        garmentStageGridRows(wrap).forEach(function (row) {
            var isLater = pastSelected;
            if (row.getAttribute('data-stage-key') === selected) {
                pastSelected = true;
            }
            if (!isLater) return;
            if (garmentRowSnapshot(row) !== (row.getAttribute('data-initial-qty') || '')) {
                blocked.push(row.getAttribute('data-stage-label'));
                row.querySelectorAll('.js-size-qty, .js-stage-damage').forEach(function (el) {
                    el.classList.add('is-invalid');
                });
            }
        });
        if (blocked.length) {
            e.preventDefault();
            var errorBox = wrap.querySelector('.js-stage-flow-error');
            if (errorBox) {
                errorBox.textContent = blocked.map(function (label) {
                    return 'Select ' + label + ' as Current Active Stage first, then enter ' + label + ' quantities.';
                }).join('\n');
                errorBox.classList.remove('d-none');
            }
            wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (!garmentValidateStageFlow(wrap)) {
            e.preventDefault();
            wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.js-autofill-stage');
        if (!btn) return;
        var wrap = btn.closest('.js-stage-grid-wrap');
        if (!wrap) return;
        var rows = garmentStageGridRows(wrap);
        var target = btn.closest('[data-stage-row]');
        var idx = rows.indexOf(target);
        if (idx <= 0) return;
        var prev = rows[idx - 1];
        prev.querySelectorAll('.js-size-qty').forEach(function (prevInput) {
            var size = prevInput.getAttribute('data-size');
            var dest = target.querySelector('.js-size-qty[data-size="' + size + '"]');
            if (dest && !dest.readOnly) dest.value = prevInput.value || 0;
        });
        garmentValidateStageFlow(wrap);
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-stage-grid-wrap').forEach(function (wrap) {
            garmentStageGridRows(wrap).forEach(function (row) {
                row.setAttribute('data-initial-qty', garmentRowSnapshot(row));
            });
            garmentValidateStageFlow(wrap);
        });
    });
</script>
@endonce
