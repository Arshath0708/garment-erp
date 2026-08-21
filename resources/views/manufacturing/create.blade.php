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

    <div class="card shadow-sm border-0 max-w-700">
        <div class="card-body p-4">
            <form action="{{ route('manufacturing.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Production Order # <span class="text-danger">*</span></label>
                    <input type="text" name="order_number" class="form-control" placeholder="e.g. PO-2026-9901" value="{{ old('order_number', 'PO-2026-' . rand(1000, 9999)) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Garment Style <span class="text-danger">*</span></label>
                    <select name="garment_style_id" class="form-select" required>
                        <option value="">— Select Garment Style —</option>
                        @foreach ($styles as $style)
                            <option value="{{ $style->id }}" {{ old('garment_style_id') == $style->id ? 'selected' : '' }}>
                                {{ $style->style_number }} — {{ $style->name }} (Buyer: {{ $style->buyer ? $style->buyer->company_name : 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Link Buyer Sales Order / OC</label>
                    <select name="order_confirmation_id" class="form-select">
                        <option value="">— Select Sales Order (Optional) —</option>
                        @foreach ($salesOrders as $so)
                            <option value="{{ $so->id }}" {{ old('order_confirmation_id') == $so->id ? 'selected' : '' }}>
                                {{ $so->oc_num }} (Buyer: {{ $so->buyer ? $so->buyer->company_name : 'N/A' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Total Order Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="total_qty" class="form-control" placeholder="e.g. 10000" value="{{ old('total_qty', 10000) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Completion Date <span class="text-danger">*</span></label>
                        <input type="date" name="target_date" class="form-control" value="{{ old('target_date', now()->addDays(30)->format('Y-m-d')) }}" required>
                    </div>
                </div>

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
</x-app-layout>
