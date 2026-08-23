@php
    $sizes = \App\Models\ProductionOrder::SIZES;
    $stages = \App\Models\ProductionOrder::STAGE_KEYS;
    $order = $order ?? null;
    $inputName = $inputName ?? 'sizes';
@endphp

<div class="table-responsive">
    <table class="table table-sm table-bordered align-middle mb-0 size-qty-grid" id="manufacturingMatrixTable">
        <thead class="table-light">
            <tr>
                <th style="min-width: 12rem">Stage / Stage Balance</th>
                @foreach ($sizes as $size)
                    <th class="text-center" style="width: 4.5rem">{{ $size }}</th>
                @endforeach
                <th class="text-center" style="width: 5rem">Total</th>
                <th class="text-center" style="width: 6.5rem">Action</th>
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
                <tr data-stage-row="{{ $key }}">
                    <td class="fw-semibold small">
                        <div>{{ $meta['label'] }}</div>
                        <span class="badge bg-secondary bg-opacity-25 text-body small js-stage-wip-badge" data-wip-for="{{ $key }}">
                            Balance: <span class="js-stage-wip-val">{{ number_format($order?->stageWipBalance($key) ?? 0) }}</span> pcs
                        </span>
                    </td>
                    @foreach ($sizes as $size)
                        <td>
                            <input type="number" min="0" step="1"
                                   name="{{ $inputName }}[{{ $key }}][{{ $size }}]"
                                   class="form-control form-control-sm text-center js-size-qty"
                                   data-stage="{{ $key }}"
                                   data-size="{{ $size }}"
                                   value="{{ old("{$inputName}.{$key}.{$size}", $order?->sizeQty($key, $size) ?? 0) }}">
                        </td>
                    @endforeach
                    <td class="text-center fw-bold js-size-row-total" data-total-for="{{ $key }}">{{ number_format($rowTotal) }}</td>
                    <td class="text-center">
                        <button type="button" class="btn btn-xs btn-outline-primary py-0 px-1 js-autofill-stage" data-stage="{{ $key }}" title="Copy remaining balance from previous stage">
                            <i class="bi bi-arrow-down-short"></i> Fill Rem.
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="alert alert-info py-2 px-3 mt-2 mb-0 small d-flex align-items-center gap-2">
    <i class="bi bi-info-circle-fill text-info fs-5"></i>
    <div>
        <strong>Automatic Stage Deduction Active:</strong> When you enter quantities for a stage (e.g. 2,500 in Stitching out of 5,000 Cut), the remaining balance (2,500 pcs) is automatically calculated and retained in the previous stage without needing manual entry.
    </div>
</div>

@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('manufacturingMatrixTable');
        if (!table) return;

        var stageOrder = ['cutting', 'printing', 'stitching', 'finishing', 'qc', 'packing', 'dispatch'];

        function updateMatrixBalances() {
            var totals = {};
            stageOrder.forEach(function (stage) {
                var row = table.querySelector('[data-stage-row="' + stage + '"]');
                if (!row) return;
                var sum = 0;
                row.querySelectorAll('.js-size-qty').forEach(function (input) {
                    sum += parseInt(input.value, 10) || 0;
                });
                totals[stage] = sum;
                var totalCell = row.querySelector('.js-size-row-total');
                if (totalCell) totalCell.textContent = sum.toLocaleString();
            });

            // Calculate auto balances
            var cuttingWip = Math.max(0, totals.cutting - Math.max(totals.printing || 0, totals.stitching || 0));
            var printingWip = Math.max(0, totals.printing - totals.stitching);
            var stitchingWip = Math.max(0, totals.stitching - totals.finishing);
            var finishingWip = Math.max(0, totals.finishing - totals.qc);
            var qcWip = Math.max(0, totals.qc - totals.packing);
            var packingWip = Math.max(0, totals.packing - totals.dispatch);

            var wips = {
                cutting: cuttingWip,
                printing: printingWip,
                stitching: stitchingWip,
                finishing: finishingWip,
                qc: qcWip,
                packing: packingWip,
                dispatch: 0
            };

            stageOrder.forEach(function (stage) {
                var badgeVal = table.querySelector('.js-stage-wip-badge[data-wip-for="' + stage + '"] .js-stage-wip-val');
                if (badgeVal) {
                    badgeVal.textContent = (wips[stage] || 0).toLocaleString();
                }
            });
        }

        table.addEventListener('input', function (e) {
            if (e.target.classList.contains('js-size-qty')) {
                updateMatrixBalances();
            }
        });

        table.addEventListener('click', function (e) {
            var btn = e.target.closest('.js-autofill-stage');
            if (!btn) return;
            var targetStage = btn.dataset.stage;
            var idx = stageOrder.indexOf(targetStage);
            if (idx <= 0) return;

            var prevStage = stageOrder[idx - 1];
            var prevRow = table.querySelector('[data-stage-row="' + prevStage + '"]');
            var targetRow = table.querySelector('[data-stage-row="' + targetStage + '"]');
            if (!prevRow || !targetRow) return;

            prevRow.querySelectorAll('.js-size-qty').forEach(function (prevInput) {
                var size = prevInput.dataset.size;
                var targetInput = targetRow.querySelector('.js-size-qty[data-size="' + size + '"]');
                if (targetInput) {
                    targetInput.value = prevInput.value || 0;
                }
            });

            updateMatrixBalances();
        });

        updateMatrixBalances();
    });
</script>
@endonce
