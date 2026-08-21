<x-app-layout>
    <x-slot name="header">Edit Production Order — {{ $order->order_number }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Modify order details, stage yields, targets, and stage allocations.</p>
        </div>
        <a href="{{ route('manufacturing.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Manufacturing
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <form action="{{ route('manufacturing.update', $order) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Production Order # <span class="text-danger">*</span></label>
                        <input type="text" name="order_number" class="form-control" value="{{ old('order_number', $order->order_number) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Current Active Stage <span class="text-danger">*</span></label>
                        <select name="current_stage" class="form-select" required>
                            <option value="Cutting" {{ old('current_stage', $order->current_stage) == 'Cutting' ? 'selected' : '' }}>Cutting</option>
                            <option value="Printing" {{ old('current_stage', $order->current_stage) == 'Printing' ? 'selected' : '' }}>Printing</option>
                            <option value="Stitching" {{ old('current_stage', $order->current_stage) == 'Stitching' ? 'selected' : '' }}>Stitching</option>
                            <option value="Finishing" {{ old('current_stage', $order->current_stage) == 'Finishing' ? 'selected' : '' }}>Finishing</option>
                            <option value="Quality Check" {{ old('current_stage', $order->current_stage) == 'Quality Check' ? 'selected' : '' }}>Quality Check</option>
                            <option value="Packing" {{ old('current_stage', $order->current_stage) == 'Packing' ? 'selected' : '' }}>Packing</option>
                            <option value="Dispatch" {{ old('current_stage', $order->current_stage) == 'Dispatch' ? 'selected' : '' }}>Dispatch</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="In Progress" {{ old('status', $order->status) == 'In Progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="On Hold" {{ old('status', $order->status) == 'On Hold' ? 'selected' : '' }}>On Hold</option>
                            <option value="Completed" {{ old('status', $order->status) == 'Completed' ? 'selected' : '' }}>Completed</option>
                            <option value="Cancelled" {{ old('status', $order->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Garment Style <span class="text-danger">*</span></label>
                        <select name="garment_style_id" class="form-select" required>
                            @foreach ($styles as $style)
                                <option value="{{ $style->id }}" {{ old('garment_style_id', $order->garment_style_id) == $style->id ? 'selected' : '' }}>
                                    {{ $style->style_number }} — {{ $style->name }} (Buyer: {{ $style->buyer ? $style->buyer->company_name : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Link Buyer Sales Order / OC</label>
                        <select name="order_confirmation_id" class="form-select">
                            <option value="">— Select Sales Order —</option>
                            @foreach ($salesOrders as $so)
                                <option value="{{ $so->id }}" {{ old('order_confirmation_id', $order->order_confirmation_id) == $so->id ? 'selected' : '' }}>
                                    {{ $so->oc_num }} (Buyer: {{ $so->buyer ? $so->buyer->company_name : 'N/A' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Total Order Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="total_qty" class="form-control" value="{{ old('total_qty', $order->total_qty) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Completion Date <span class="text-danger">*</span></label>
                        <input type="date" name="target_date" class="form-control" value="{{ old('target_date', $order->target_date ? $order->target_date->format('Y-m-d') : '') }}" required>
                    </div>
                </div>

                <!-- Stage Quantities Grid -->
                <div class="p-3 bg-body-tertiary border rounded mb-4">
                    <h6 class="fw-bold mb-3 text-primary"><i class="bi bi-bar-chart-steps me-1"></i> Process Stage Yield Quantities (pcs)</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Cutting Qty</label>
                            <input type="number" name="cutting_qty" class="form-control" value="{{ old('cutting_qty', $order->cutting_qty) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Stitching Qty</label>
                            <input type="number" name="stitching_qty" class="form-control" value="{{ old('stitching_qty', $order->stitching_qty) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Finishing Qty</label>
                            <input type="number" name="finishing_qty" class="form-control" value="{{ old('finishing_qty', $order->finishing_qty) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">QC Passed Qty</label>
                            <input type="number" name="qc_passed_qty" class="form-control" value="{{ old('qc_passed_qty', $order->qc_passed_qty) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">QC Rejected Qty</label>
                            <input type="number" name="qc_rejected_qty" class="form-control" value="{{ old('qc_rejected_qty', $order->qc_rejected_qty) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Packing Qty</label>
                            <input type="number" name="packing_qty" class="form-control" value="{{ old('packing_qty', $order->packing_qty) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Dispatch Qty</label>
                            <input type="number" name="dispatch_qty" class="form-control" value="{{ old('dispatch_qty', $order->dispatch_qty) }}">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Planning & Floor Notes</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes', $order->notes) }}</textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('manufacturing.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Update Production Order</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
