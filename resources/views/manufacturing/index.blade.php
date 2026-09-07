<x-app-layout>
    <x-slot name="header">Manufacturing Processes & Production Tracking</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Track live progress across Cutting, Stitching, Finishing, Quality Check, Packing, and Dispatch.</p>
        </div>
        <a href="{{ route('manufacturing.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Production Order
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Factory Floor Barcode & Order Quick Scanner Search -->
    <div class="card shadow-sm border-0 mb-4 bg-body border-start border-primary border-4 p-3">
        <div class="row align-items-center g-3">
            <div class="col-md-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="bi bi-upc-scan fs-3"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Factory Barcode & Ticket Scan</h6>
                        <span class="text-body-secondary small">Scan bundle tag or type Order # / Style No</span>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-primary text-white border-primary"><i class="bi bi-upc-scan"></i></span>
                    <input type="text" id="manufacturing-scan-input" class="form-control form-control-lg fw-bold border-primary"
                           placeholder="Scan barcode or type PO / Style #"
                           enterkeyhint="search" autofocus autocomplete="off" style="min-height: 52px; font-size: 1.1rem;">
                    <button type="button" class="btn btn-outline-secondary" id="btn-clear-scan" title="Clear Search" style="min-height: 52px; min-width: 48px;">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Manufacturing Stage Pipeline Guide -->
    @php
        $activePipeline = request('stage');
        $pipelineSteps = [
            ['label' => '1. Buyer Sales Order', 'href' => route('sales.order-confirmations.index'), 'key' => 'sales'],
            ['label' => '2. Style & BOM', 'href' => route('masters.styles.index'), 'key' => 'bom'],
            ['label' => '3. Work Order', 'href' => route('work-orders.index'), 'key' => 'work-order'],
            ['label' => '4. Cutting', 'href' => route('manufacturing.index', ['stage' => 'Cutting']), 'key' => 'Cutting'],
            ['label' => '5. Printing / Embroidery', 'href' => route('manufacturing.index', ['stage' => 'Printing']), 'key' => 'Printing'],
            ['label' => '6. Stitching', 'href' => route('manufacturing.index', ['stage' => 'Stitching']), 'key' => 'Stitching'],
            ['label' => '7. Finishing', 'href' => route('manufacturing.index', ['stage' => 'Finishing']), 'key' => 'Finishing'],
            ['label' => '8. Quality Check', 'href' => route('manufacturing.index', ['stage' => 'Quality Check']), 'key' => 'Quality Check'],
            ['label' => '9. Packing & Dispatch', 'href' => route('manufacturing.index', ['stage' => 'Packing']), 'key' => 'Packing'],
        ];
    @endphp
    <div class="card shadow-sm border-0 mb-4 bg-dark text-white p-3">
        <div class="text-uppercase text-info fw-bold small mb-2"><i class="bi bi-diagram-3-fill me-1"></i> Garment Manufacturing Order Pipeline</div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 text-center text-white-50 small">
            @foreach ($pipelineSteps as $i => $step)
                @if($i > 0)
                    <i class="bi bi-arrow-right"></i>
                @endif
                @php $isActive = $activePipeline !== null && $activePipeline !== '' && $activePipeline === $step['key']; @endphp
                <a href="{{ $step['href'] }}"
                   data-pipeline-stage="{{ $step['key'] }}"
                   class="px-3 py-2 rounded text-decoration-none {{ $isActive ? 'bg-info text-dark fw-bold' : 'bg-secondary bg-opacity-25 text-white' }}">
                    {{ $step['label'] }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Production Orders Grid -->
    <div class="row g-4">
        @forelse ($orders as $order)
            <div class="col-lg-6" data-order-card
                 data-order-number="{{ $order->order_number }}"
                 data-style-number="{{ $order->garmentStyle?->style_number }}"
                 data-buyer-style="{{ $order->garmentStyle?->buyer_style_no }}"
                 data-factory-style="{{ $order->garmentStyle?->factory_style_no }}">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">{{ $order->order_number }}</span>
                            <strong class="text-body">{{ $order->garmentStyle ? $order->garmentStyle->style_number . ' — ' . $order->garmentStyle->name : 'N/A' }}</strong>
                        </div>
                        <span class="badge bg-warning text-dark">{{ $order->current_stage }} Stage</span>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 mb-3 small">
                            <div class="col-6">Factory Style No: <strong class="text-dark">{{ $order->garmentStyle?->factory_style_no ?: '—' }}</strong></div>
                            <div class="col-6 text-end">Buyer Style No: <strong>{{ $order->garmentStyle?->buyer_style_no ?: '—' }}</strong></div>
                            <div class="col-6">Buyer: <strong>{{ $order->buyer ? $order->buyer->company_name : 'N/A' }}</strong></div>
                            <div class="col-6 text-end">Target Date: <strong>{{ $order->target_date ? $order->target_date->format('Y-m-d') : 'N/A' }}</strong></div>
                            <div class="col-6">Total Order Qty: <strong class="text-primary">{{ number_format($order->total_qty) }} pcs</strong></div>
                            <div class="col-6 text-end">Dispatch Qty: <strong class="text-success">{{ number_format($order->dispatch_qty) }} pcs</strong></div>
                            @if($order->workOrder)
                                <div class="col-12">Work order: <a href="{{ route('work-orders.show', $order->workOrder) }}" class="font-monospace">{{ $order->workOrder->wo_num }}</a></div>
                            @endif
                            @if($order->job_work_type && $order->job_work_type !== 'in_house')
                                <div class="col-12">Job work: <strong>{{ $order->jobWorkTypeLabel() }}</strong>
                                    @if($order->jobber) · {{ $order->jobber->company_name }} @endif
                                </div>
                            @endif
                        </div>

                        @if($order->garmentStyle && $order->garmentStyle->comments->isNotEmpty())
                            <div class="p-2 mb-3 bg-warning bg-opacity-10 border border-warning rounded small">
                                <div class="fw-bold text-dark mb-1">
                                    <i class="bi bi-chat-left-text me-1 text-warning"></i> Style &amp; Tech Pack Comments ({{ $order->garmentStyle->comments->count() }}):
                                </div>
                                <ul class="list-unstyled mb-0 ps-1">
                                    @foreach($order->garmentStyle->comments->take(3) as $c)
                                        <li class="mb-1 text-body">
                                            <strong class="text-primary">{{ $c->user_name }}</strong> <span class="text-body-secondary">({{ $c->created_at->format('Y-m-d H:i') }}):</span> {{ $c->comment }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <!-- Stage Breakdown Metrics -->
                        <div class="p-3 bg-body-tertiary rounded mb-3">
                            <div class="row g-2 text-center small">
                                <div class="col border-end">
                                    <div class="text-body-secondary">Cutting</div>
                                    <div class="fw-bold">{{ number_format($order->cutting_qty) }}</div>
                                </div>
                                <div class="col border-end">
                                    <div class="text-body-secondary">Print / Emb</div>
                                    <div class="fw-bold">{{ number_format($order->printing_qty) }}</div>
                                </div>
                                <div class="col border-end">
                                    <div class="text-body-secondary">Stitching</div>
                                    <div class="fw-bold">{{ number_format($order->stitching_qty) }}</div>
                                </div>
                                <div class="col border-end">
                                    <div class="text-body-secondary">Finishing</div>
                                    <div class="fw-bold">{{ number_format($order->finishing_qty) }}</div>
                                </div>
                                <div class="col border-end">
                                    <div class="text-body-secondary">QC Pass</div>
                                    <div class="fw-bold text-success">{{ number_format($order->qc_passed_qty) }}</div>
                                </div>
                                <div class="col border-end">
                                    <div class="text-body-secondary">Packing</div>
                                    <div class="fw-bold">{{ number_format($order->packing_qty) }}</div>
                                </div>
                                <div class="col">
                                    <div class="text-body-secondary">Dispatch</div>
                                    <div class="fw-bold text-primary">{{ number_format($order->dispatch_qty) }}</div>
                                </div>
                            </div>
                            @php $stageRows = $order->filledStageRows(); @endphp
                            @if($stageRows !== [])
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered mb-0 small">
                                        <thead>
                                            <tr>
                                                <th class="text-body-secondary fw-normal">Stage sizes</th>
                                                @foreach (\App\Models\ProductionOrder::SIZES as $size)
                                                    <th class="text-center">{{ $size }}</th>
                                                @endforeach
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Damage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($stageRows as $row)
                                                <tr>
                                                    <td class="fw-semibold">{{ $row['label'] }}</td>
                                                    @foreach (\App\Models\ProductionOrder::SIZES as $size)
                                                        <td class="text-center">{{ number_format($row['sizes'][$size] ?? 0) }}</td>
                                                    @endforeach
                                                    <td class="text-center fw-bold">{{ number_format($row['total']) }}</td>
                                                    <td class="text-center text-danger">{{ number_format($row['damage']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        @php
                            $completedPct = min(100, round(($order->dispatch_qty / max(1, $order->total_qty)) * 100));
                        @endphp
                        <div class="progress mb-2" style="height: 8px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $completedPct }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completion: {{ $completedPct }}%</span>
                            <span>Notes: {{ $order->notes ?: 'Operating normally' }}</span>
                        </div>
                    </div>
                    <div class="card-footer bg-body border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <form action="{{ route('manufacturing.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this production order?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger touch-action-btn" title="Delete Production Order">
                                    <i class="bi bi-trash me-1"></i> Delete
                                </button>
                            </form>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('manufacturing.job-work-challan', $order) }}" class="btn btn-outline-primary touch-action-btn" title="Size-wise job-work delivery challan">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Challan
                            </a>
                            <a href="{{ route('manufacturing.edit', $order) }}" class="btn btn-outline-secondary touch-action-btn">
                                <i class="bi bi-pencil-square me-1"></i> Edit Order
                            </a>
                            <button class="btn btn-primary touch-action-btn fw-bold px-3" data-bs-toggle="modal" data-bs-target="#updateStageModal{{ $order->id }}">
                                <i class="bi bi-pencil-square me-1"></i> Update Stage Quantities
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="updateStageModal{{ $order->id }}" tabindex="-1">
                <div class="modal-dialog modal-xl modal-fullscreen-lg-down">
                    <div class="modal-content">
                        <form action="{{ route('manufacturing.update-stage', $order) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-dark text-white">
                                <h5 class="modal-title fw-bold">Update Production Stage — {{ $order->order_number }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-3 p-md-4">
                                <div class="mb-4 p-3 bg-body-tertiary rounded border">
                                    <label class="form-label fw-bold text-primary fs-6">Current Active Stage</label>
                                    <select name="current_stage" class="form-select form-select-lg fw-bold js-current-stage" style="min-height: 48px;">
                                        <option value="Cutting" {{ $order->current_stage == 'Cutting' ? 'selected' : '' }}>Cutting</option>
                                        <option value="Printing" {{ $order->current_stage == 'Printing' ? 'selected' : '' }}>Printing / Embroidery</option>
                                        <option value="Stitching" {{ $order->current_stage == 'Stitching' ? 'selected' : '' }}>Stitching</option>
                                        <option value="Finishing" {{ $order->current_stage == 'Finishing' ? 'selected' : '' }}>Finishing</option>
                                        <option value="Quality Check" {{ $order->current_stage == 'Quality Check' ? 'selected' : '' }}>Quality Check</option>
                                        <option value="Packing" {{ $order->current_stage == 'Packing' ? 'selected' : '' }}>Packing</option>
                                        <option value="Dispatch" {{ $order->current_stage == 'Dispatch' ? 'selected' : '' }}>Dispatch</option>
                                    </select>
                                </div>
                                @include('manufacturing._size_matrix', ['order' => $order])
                            </div>
                            <div class="modal-footer p-3 bg-light">
                                <button type="button" class="btn btn-secondary py-2 px-4 touch-action-btn" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary py-2 px-4 touch-action-btn fw-bold fs-6">
                                    <i class="bi bi-check-circle me-1"></i> Save Progress
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5 text-body-secondary">
                <i class="bi bi-gear-wide-connected fs-1 d-block mb-2"></i>
                No production orders found. Click <strong>New Production Order</strong> to create one.
            </div>
        @endforelse
    </div>

    <style>
        .touch-action-btn {
            min-height: 48px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
    </style>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const scanInput = document.getElementById('manufacturing-scan-input');
        const clearBtn = document.getElementById('btn-clear-scan');
        if (!scanInput) return;

        function filterOrders() {
            const query = (scanInput.value || '').trim().toLowerCase();
            const cards = document.querySelectorAll('[data-order-card]');
            let visibleCount = 0;

            cards.forEach(card => {
                const num = (card.getAttribute('data-order-number') || '').toLowerCase();
                const style = (card.getAttribute('data-style-number') || '').toLowerCase();
                const buyerStyle = (card.getAttribute('data-buyer-style') || '').toLowerCase();
                const factoryStyle = (card.getAttribute('data-factory-style') || '').toLowerCase();

                const match = !query || num.includes(query) || style.includes(query) || buyerStyle.includes(query) || factoryStyle.includes(query);
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
        }

        scanInput.addEventListener('input', filterOrders);

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                scanInput.value = '';
                filterOrders();
                scanInput.focus();
            });
        }

        // Handle Enter key for hardware / keyboard barcode scanners
        scanInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const visibleCards = Array.from(document.querySelectorAll('[data-order-card]')).filter(c => c.style.display !== 'none');
                if (visibleCards.length === 1) {
                    const modalBtn = visibleCards[0].querySelector('[data-bs-target^="#updateStageModal"]');
                    if (modalBtn) modalBtn.click();
                }
            }
        });

        // Auto-focus active inputs when stage update modal opens
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('shown.bs.modal', function () {
                const firstInput = modal.querySelector('input.js-size-qty:not([readonly])');
                if (firstInput) firstInput.focus();
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
