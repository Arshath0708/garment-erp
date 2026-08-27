<x-app-layout>
    <x-slot name="header">Create New Garment Style</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Define style specs, buyer reference, fabric composition, colorways, and sizes.</p>
        </div>
        <a href="{{ route('masters.styles.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Styles
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
            <form action="{{ route('masters.styles.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Style Number <span class="text-danger">*</span></label>
                        <input type="text" name="style_number" class="form-control" placeholder="e.g. ST-1005" value="{{ old('style_number') }}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Style Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Men's Woven Casual Shirt" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" {{ old('status', 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="Draft" {{ old('status') == 'Draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                </div>


                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Buyer / Customer</label>
                        <select name="buyer_id" class="form-select">
                            <option value="">— Select Buyer —</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" {{ old('buyer_id') == $buyer->id ? 'selected' : '' }}>
                                    {{ $buyer->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Garment Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">— Select Category —</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Season</label>
                        <input type="text" name="season" class="form-control" placeholder="e.g. Autumn / Winter 2026" value="{{ old('season') }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Colorway / Color</label>
                        <input type="text" name="color" class="form-control" placeholder="e.g. Navy Blue / White" value="{{ old('color') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Design / Fit Type</label>
                        <input type="text" name="design" class="form-control" placeholder="e.g. Slim Fit Button Down" value="{{ old('design') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fabric Composition & GSM</label>
                        <input type="text" name="fabric" class="form-control" placeholder="e.g. 100% Cotton Twill 180GSM" value="{{ old('fabric') }}">
                    </div>
                </div>

                <div class="card bg-body-tertiary border mb-4">
                    <div class="card-header bg-body d-flex justify-content-between align-items-center py-3">
                        <div>
                            <h6 class="fw-bold mb-0"><i class="bi bi-rulers me-2 text-primary"></i> Size &amp; Quantity Breakdown Matrix</h6>
                            <small class="text-body-secondary">Add unlimited size breakdown entries. Click (+) to add more sizes.</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="addSizeRowBtn">
                            <i class="bi bi-plus-circle me-1"></i> Add Size
                        </button>
                    </div>
                    <div class="card-body p-3">
                        <div id="sizeRowsContainer">
                            <div class="row g-2 align-items-center mb-2 size-row">
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-body">Size</span>
                                        <input type="text" name="size_names[]" class="form-control form-control-sm size-name-input" placeholder="e.g. M, L, XL, 38, 40" value="M" required>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-body">Quantity</span>
                                        <input type="number" name="size_qtys[]" class="form-control form-control-sm size-qty-input" placeholder="e.g. 100" value="100" min="0" required>
                                        <span class="input-group-text bg-body">pcs</span>
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-row" title="Add Another Size">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove Size">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                            <span class="fw-semibold text-body-secondary small">Calculated Total Target Batch Quantity:</span>
                            <span class="fs-6 fw-bold text-primary" id="calculatedTotalQty">100 pcs</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6 d-none">
                        <input type="hidden" name="sizes" id="hiddenSizesInput" value="{{ old('sizes', 'M (100 pcs)') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Batch Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="target_qty" id="targetQtyInput" class="form-control" placeholder="e.g. 10000" value="{{ old('target_qty', 100) }}" required>
                        <small class="text-body-secondary">Auto-calculated from size breakdown sum above.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Technical Specifications & Construction Notes</label>
                    <textarea name="tech_specs" class="form-control" rows="4" placeholder="Enter seam allowance, thread type, interlining, washing treatment details...">{{ old('tech_specs') }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Upload Logo / Garment Sketch Image</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                </div>

                @include('masters.styles._materials', ['products' => $products, 'style' => null])

                <div class="text-end">
                    <a href="{{ route('masters.styles.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Save Garment Style</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('sizeRowsContainer');
        const addBtn = document.getElementById('addSizeRowBtn');
        const totalQtyEl = document.getElementById('calculatedTotalQty');
        const targetQtyInput = document.getElementById('targetQtyInput');
        const hiddenSizesInput = document.getElementById('hiddenSizesInput');
        const defaultSizes = ['M', 'L', 'XL', '2XL', 'S', 'XS', '3XL'];

        function createRow(sizeName = '', sizeQty = 100) {
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-center mb-2 size-row';
            row.innerHTML = `
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body">Size</span>
                        <input type="text" name="size_names[]" class="form-control form-control-sm size-name-input" placeholder="e.g. M, L, XL, 38, 40" value="${sizeName}" required>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-body">Quantity</span>
                        <input type="number" name="size_qtys[]" class="form-control form-control-sm size-qty-input" placeholder="e.g. 100" value="${sizeQty}" min="0" required>
                        <span class="input-group-text bg-body">pcs</span>
                    </div>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-row" title="Add Another Size"><i class="bi bi-plus-lg"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" title="Remove Size"><i class="bi bi-trash"></i></button>
                </div>
            `;
            return row;
        }

        function recalculateTotals() {
            let total = 0;
            const sizeSummaries = [];
            container.querySelectorAll('.size-row').forEach(row => {
                const name = row.querySelector('.size-name-input')?.value.trim() || '';
                const qty = parseInt(row.querySelector('.size-qty-input')?.value, 10) || 0;
                total += qty;
                if (name) sizeSummaries.push(qty > 0 ? `${name} (${qty} pcs)` : name);
            });
            if (totalQtyEl) totalQtyEl.textContent = total.toLocaleString() + ' pcs';
            if (targetQtyInput) targetQtyInput.value = total;
            if (hiddenSizesInput) hiddenSizesInput.value = sizeSummaries.join(', ');
        }

        function suggestNextSize() {
            const existing = Array.from(container.querySelectorAll('.size-name-input')).map(i => i.value.trim().toUpperCase());
            for (let s of defaultSizes) {
                if (!existing.includes(s)) return s;
            }
            return 'Custom Size';
        }

        addBtn?.addEventListener('click', function () {
            container.appendChild(createRow(suggestNextSize(), 100));
            recalculateTotals();
        });

        container?.addEventListener('click', function (e) {
            if (e.target.closest('.btn-add-row')) {
                e.target.closest('.size-row').after(createRow(suggestNextSize(), 100));
                recalculateTotals();
            } else if (e.target.closest('.btn-remove-row')) {
                const rows = container.querySelectorAll('.size-row');
                if (rows.length > 1) {
                    e.target.closest('.size-row').remove();
                    recalculateTotals();
                }
            }
        });

        container?.addEventListener('input', function (e) {
            if (e.target.classList.contains('size-qty-input') || e.target.classList.contains('size-name-input')) {
                recalculateTotals();
            }
        });

        recalculateTotals();
    });
    </script>
    @endpush
</x-app-layout>
