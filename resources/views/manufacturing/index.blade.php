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

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Manufacturing Stage Pipeline Guide -->
    <div class="card shadow-sm border-0 mb-4 bg-dark text-white p-3">
        <div class="text-uppercase text-info fw-bold small mb-2"><i class="bi bi-diagram-3-fill me-1"></i> Garment Manufacturing Order Pipeline</div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 text-center text-white-50 small">
            <div class="px-3 py-2 bg-secondary bg-opacity-25 rounded"><strong class="text-white">1. Buyer Sales Order</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-secondary bg-opacity-25 rounded"><strong class="text-white">2. Style & BOM</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-primary bg-opacity-50 rounded text-white"><strong class="text-white">3. Cutting</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-info bg-opacity-25 rounded text-info"><strong class="text-info">4. Printing / Embroidery</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-primary bg-opacity-50 rounded text-white"><strong class="text-white">5. Stitching</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-warning bg-opacity-25 rounded text-warning"><strong class="text-warning">6. Finishing</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-success bg-opacity-25 rounded text-success"><strong class="text-success">7. Quality Check</strong></div>
            <i class="bi bi-arrow-right"></i>
            <div class="px-3 py-2 bg-info bg-opacity-25 rounded text-info"><strong class="text-info">8. Packing & Dispatch</strong></div>
        </div>
    </div>

    <!-- Production Orders Grid -->
    <div class="row g-4">
        @forelse ($orders as $order)
            <div class="col-lg-6">
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
                            <div class="col-6">Buyer: <strong>{{ $order->buyer ? $order->buyer->company_name : 'N/A' }}</strong></div>
                            <div class="col-6 text-end">Target Date: <strong>{{ $order->target_date ? $order->target_date->format('Y-m-d') : 'N/A' }}</strong></div>
                            <div class="col-6">Total Order Qty: <strong class="text-primary">{{ number_format($order->total_qty) }} pcs</strong></div>
                            <div class="col-6 text-end">Dispatch Qty: <strong class="text-success">{{ number_format($order->dispatch_qty) }} pcs</strong></div>
                            @if($order->job_work_type && $order->job_work_type !== 'in_house')
                                <div class="col-12">Job work: <strong>{{ $order->jobWorkTypeLabel() }}</strong>
                                    @if($order->jobber) · {{ $order->jobber->company_name }} @endif
                                </div>
                            @endif
                        </div>

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
                            @php $cutSizes = $order->size_breakdown['cutting'] ?? []; @endphp
                            @if(collect($cutSizes)->sum() > 0)
                                <div class="table-responsive mt-3">
                                    <table class="table table-sm table-bordered mb-0 small">
                                        <thead>
                                            <tr>
                                                <th class="text-body-secondary fw-normal">Cutting sizes</th>
                                                @foreach (\App\Models\ProductionOrder::SIZES as $size)
                                                    <th class="text-center">{{ $size }}</th>
                                                @endforeach
                                                <th class="text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td></td>
                                                @foreach (\App\Models\ProductionOrder::SIZES as $size)
                                                    <td class="text-center">{{ number_format((int) ($cutSizes[$size] ?? 0)) }}</td>
                                                @endforeach
                                                <td class="text-center fw-bold">{{ number_format($order->stageSizeTotal('cutting')) }}</td>
                                            </tr>
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
                    <div class="card-footer bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                        <div>
                            <form action="{{ route('manufacturing.destroy', $order) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this production order?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Production Order">
                                    <i class="bi bi-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('manufacturing.job-work-challan', $order) }}" class="btn btn-sm btn-outline-primary" title="Size-wise job-work delivery challan">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Challan
                            </a>
                            <a href="{{ route('manufacturing.edit', $order) }}" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-pencil-square me-1"></i> Edit Order
                            </a>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#updateStageModal{{ $order->id }}">
                                <i class="bi bi-pencil-square me-1"></i> Update Stage Quantities
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal fade" id="updateStageModal{{ $order->id }}" tabindex="-1">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <form action="{{ route('manufacturing.update-stage', $order) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Update Production Stage — {{ $order->order_number }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Current Active Stage</label>
                                    <select name="current_stage" class="form-select">
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
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Save Progress</button>
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
</x-app-layout>
