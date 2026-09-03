<x-app-layout>
    <x-slot name="header">New Production Order</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Link a Production Order to a Garment Style and Buyer Sales Order.</p>
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
            <form action="{{ route('manufacturing.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Production Order # <span class="text-danger">*</span></label>
                    <input type="text" name="order_number" class="form-control" placeholder="e.g. PO-2026-9901" value="{{ old('order_number', 'PO-2026-' . rand(1000, 9999)) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Released Work Order <span class="text-danger">*</span></label>
                    <select name="work_order_id" id="work_order_id" class="form-select @error('work_order_id') is-invalid @enderror" required>
                        <option value="">— Select released work order —</option>
                        @foreach ($workOrders as $wo)
                            <option value="{{ $wo->id }}"
                                {{ (string) old('work_order_id', $selectedWorkOrderId) === (string) $wo->id ? 'selected' : '' }}
                                data-style="{{ $wo->garment_style_id }}"
                                data-oc="{{ $wo->order_confirmation_id }}"
                                data-qty="{{ $wo->total_qty }}"
                                data-target="{{ $wo->target_date?->format('Y-m-d') }}">
                                {{ $wo->wo_num }} — {{ $wo->garmentStyle?->style_number }} ({{ $wo->statusLabel() }})
                            </option>
                        @endforeach
                    </select>
                    @error('work_order_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @if($workOrders->isEmpty())
                        <div class="form-text text-danger">
                            No released work order yet.
                            <a href="{{ route('work-orders.create') }}">Create one</a>, then click Release.
                        </div>
                    @else
                        <div class="form-text">Draft / Hold work orders do not appear here. Style and qty below should match the work order.</div>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Garment Style <span class="text-danger">*</span></label>
                    <select name="garment_style_id" id="garment_style_id" class="form-select" required>
                        <option value="">— Select Garment Style —</option>
                        @foreach ($styles as $style)
                            <option value="{{ $style->id }}" {{ (string) old('garment_style_id', $selectedWorkOrder?->garment_style_id) === (string) $style->id ? 'selected' : '' }}>
                                {{ $style->style_number }} — {{ $style->name }} (Buyer: {{ $style->buyer ? $style->buyer->company_name : 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Buyer Sales Order / OC</label>
                    <select name="order_confirmation_id" id="order_confirmation_id" class="form-select">
                        <option value="">— Select Sales Order (Optional) —</option>
                        @foreach ($salesOrders as $so)
                            <option value="{{ $so->id }}" {{ (string) old('order_confirmation_id', $selectedWorkOrder?->order_confirmation_id) === (string) $so->id ? 'selected' : '' }}>
                                {{ $so->oc_num }} (Buyer: {{ $so->buyer ? $so->buyer->company_name : 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Total Order Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="total_qty" id="total_qty" class="form-control" placeholder="e.g. 10000" value="{{ old('total_qty', $selectedWorkOrder?->total_qty ?? 10000) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Completion Date <span class="text-danger">*</span></label>
                        <input type="date" name="target_date" id="target_date" class="form-control" value="{{ old('target_date', $selectedWorkOrder?->target_date?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d')) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Job work type</label>
                        <select name="job_work_type" class="form-select">
                            @foreach (\App\Models\ProductionOrder::JOB_WORK_TYPES as $value => $label)
                                <option value="{{ $value }}" {{ old('job_work_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Printing / embroidery jobbers get a size-wise delivery challan.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Jobber (challan “M/S”)</label>
                        <select name="jobber_id" class="form-select">
                            <option value="">— Own floor / none —</option>
                            @foreach ($jobbers as $jobber)
                                <option value="{{ $jobber->id }}" {{ (string) old('jobber_id') === (string) $jobber->id ? 'selected' : '' }}>
                                    {{ $jobber->company_name }} ({{ $jobber->display_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Place of supply</label>
                        <input type="text" name="place_of_supply" class="form-control" value="{{ old('place_of_supply') }}" placeholder="e.g. Ahmedabad">
                    </div>
                </div>

                <div class="p-3 bg-body-tertiary border rounded mb-4">
                    <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-grid-3x3-gap me-1"></i> Order qty by size (S–5XL)</h6>
                    <p class="small text-body-secondary">Fill Cutting row for what you are sending / starting. Other stages can stay 0 until the floor updates them.</p>
                    @include('manufacturing._size_matrix', ['order' => null])
                </div>

                @include('manufacturing._material_plan', ['planRows' => []])

                <div class="mb-4">
                    <label class="form-label fw-semibold">Planning Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Enter line assignment, SAM target, or special instructions...">{{ old('notes') }}</textarea>
                </div>

                <div class="text-end">
                    <a href="{{ route('manufacturing.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Launch Production Order</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const wo = document.getElementById('work_order_id');
            if (!wo) return;

            const fillFromWorkOrder = () => {
                const option = wo.options[wo.selectedIndex];
                if (!option || !option.value) return;

                const style = option.getAttribute('data-style');
                const oc = option.getAttribute('data-oc');
                const qty = option.getAttribute('data-qty');
                const target = option.getAttribute('data-target');

                const styleEl = document.getElementById('garment_style_id');
                const ocEl = document.getElementById('order_confirmation_id');
                const qtyEl = document.getElementById('total_qty');
                const targetEl = document.getElementById('target_date');

                if (styleEl && style) styleEl.value = style;
                if (ocEl) ocEl.value = oc || '';
                if (qtyEl && qty) qtyEl.value = qty;
                if (targetEl && target) targetEl.value = target;
            };

            wo.addEventListener('change', fillFromWorkOrder);
        })();
    </script>
    @endpush
</x-app-layout>
