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

    @if($order->garmentStyle)
        <div class="card shadow-sm border-0 mb-4 bg-body-tertiary">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <span class="badge bg-dark me-2">Factory Style No: {{ $order->garmentStyle->factory_style_no ?: 'Not set' }}</span>
                        <span class="badge bg-outline-secondary me-2">Buyer Style No: {{ $order->garmentStyle->buyer_style_no ?: 'Not set' }}</span>
                        <strong>Style Code: {{ $order->garmentStyle->style_number }} — {{ $order->garmentStyle->name }}</strong>
                    </div>
                    <a href="{{ route('masters.styles.show', $order->garmentStyle->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Tech Pack
                    </a>
                </div>

                @if($order->garmentStyle->comments->isNotEmpty())
                    <div class="p-3 bg-white rounded border">
                        <div class="fw-bold text-primary mb-2">
                            <i class="bi bi-chat-left-text-fill me-1 text-warning"></i> Pre-Production Style &amp; Tech Pack Instructions:
                        </div>
                        <div class="vstack gap-2">
                            @foreach($order->garmentStyle->comments as $c)
                                <div class="p-2 bg-body-tertiary rounded small border-start border-3 border-warning">
                                    <div class="d-flex justify-content-between text-body-secondary mb-1">
                                        <strong>{{ $c->user_name }}</strong>
                                        <small>{{ $c->created_at->format('Y-m-d H:i') }}</small>
                                    </div>
                                    <div class="text-body" style="white-space: pre-line;">{{ $c->comment }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
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
                        <select name="current_stage" class="form-select js-current-stage" required>
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

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Job work type</label>
                        <select name="job_work_type" class="form-select">
                            @foreach (\App\Models\ProductionOrder::JOB_WORK_TYPES as $value => $label)
                                <option value="{{ $value }}" @selected(old('job_work_type', $order->job_work_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jobber (challan “M/S”)</label>
                        <select name="jobber_id" class="form-select">
                            <option value="">— Own floor / none —</option>
                            @foreach ($jobbers as $jobber)
                                <option value="{{ $jobber->id }}" @selected((string) old('jobber_id', $order->jobber_id) === (string) $jobber->id)>
                                    {{ $jobber->company_name }} ({{ $jobber->display_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Place of supply</label>
                        <input type="text" name="place_of_supply" class="form-control" value="{{ old('place_of_supply', $order->place_of_supply) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Challan no.</label>
                        <input type="text" name="challan_no" class="form-control" value="{{ old('challan_no', $order->challan_no) }}" placeholder="e.g. VD-DC-3236">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Driver name</label>
                        <input type="text" name="driver_name" class="form-control" value="{{ old('driver_name', $order->driver_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Vehicle no.</label>
                        <input type="text" name="vehicle_no" class="form-control" value="{{ old('vehicle_no', $order->vehicle_no) }}">
                    </div>
                </div>

                <div class="p-3 bg-body-tertiary border rounded mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-grid-3x3-gap me-1"></i> Size-wise stage qty (S–5XL)</h6>
                        <a href="{{ route('manufacturing.job-work-challan', $order) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Job Work Delivery Challan
                        </a>
                    </div>
                    @include('manufacturing._size_matrix', ['order' => $order])
                </div>

                @include('manufacturing._material_plan', ['planRows' => $planRows ?? [], 'order' => $order])

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
