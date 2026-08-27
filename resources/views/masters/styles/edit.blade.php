<x-app-layout>
    <x-slot name="header">Edit Garment Style — {{ $style->style_number }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Modify style specs, buyer reference, fabric composition, colorways, and sizes.</p>
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
            <form action="{{ route('masters.styles.update', $style) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Style Number <span class="text-danger">*</span></label>
                        <input type="text" name="style_number" class="form-control" value="{{ old('style_number', $style->style_number) }}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-semibold">Style Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $style->name) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" {{ old('status', $style->status) == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ old('status', $style->status) == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="Draft" {{ old('status', $style->status) == 'Draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Buyer / Customer</label>
                        <select name="buyer_id" class="form-select">
                            <option value="">— Select Buyer —</option>
                            @foreach ($buyers as $buyer)
                                <option value="{{ $buyer->id }}" {{ old('buyer_id', $style->buyer_id) == $buyer->id ? 'selected' : '' }}>
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
                                <option value="{{ $category->id }}" {{ old('category_id', $style->category_id) == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Season</label>
                        <input type="text" name="season" class="form-control" value="{{ old('season', $style->season) }}">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Colorway / Color</label>
                        <input type="text" name="color" class="form-control" value="{{ old('color', $style->color) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Design / Fit Type</label>
                        <input type="text" name="design" class="form-control" value="{{ old('design', $style->design) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Fabric Composition & GSM</label>
                        <input type="text" name="fabric" class="form-control" value="{{ old('fabric', $style->fabric) }}">
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
                        <div id="sizeRowsContainer"></div>
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-2">
                            <span class="fw-semibold text-body-secondary small">Calculated Total Target Batch Quantity:</span>
                            <span class="fs-6 fw-bold text-primary" id="calculatedTotalQty">{{ number_format($style->target_qty) }} pcs</span>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6 d-none">
                        <input type="hidden" name="sizes" id="hiddenSizesInput" value="{{ old('sizes', $style->sizes) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Batch Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="target_qty" id="targetQtyInput" class="form-control" value="{{ old('target_qty', $style->target_qty) }}" required>
                        <small class="text-body-secondary">Auto-calculated from size breakdown sum above.</small>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Technical Specifications & Construction Notes</label>
                    <textarea name="tech_specs" class="form-control" rows="4">{{ old('tech_specs', $style->tech_specs) }}</textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Upload Logo / Garment Sketch Image</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    @if ($style->logo_path)
                        <div class="mt-2 text-muted small">
                            Current image: <a href="{{ asset('storage/' . $style->logo_path) }}" target="_blank">View File</a>
                        </div>
                    @endif
                </div>

                @include('masters.styles._materials', ['products' => $products, 'style' => $style])

                <div class="text-end">
                    <a href="{{ route('masters.styles.index') }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i> Update Garment Style</button>
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
        const rawSizesString = @json($style->sizes ?? '');
        const currentTargetQty = parseInt(@json($style->target_qty ?? 0), 10) || 0;
        const defaultSizes = ['M', 'L', 'XL', '2XL', 'S', 'XS', '3XL'];

        function createRow(sizeName = '', sizeQty = 0) {
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

        function parseExistingSizes() {
            if (!rawSizesString) {
                container.appendChild(createRow('M', currentTargetQty || 100));
                return;
            }
            const parts = rawSizesString.split(',').map(s => s.trim()).filter(Boolean);
            if (parts.length === 0) {
                container.appendChild(createRow('M', currentTargetQty || 100));
                return;
            }
            parts.forEach(part => {
                const match = part.match(/^(.+?)\s*\((?:(\d+)(?:\s*pcs)?)?\)$/i);
                if (match) {
                    container.appendChild(createRow(match[1].trim(), parseInt(match[2], 10) || 0));
                } else {
                    const splitQty = Math.floor(currentTargetQty / parts.length);
                    container.appendChild(createRow(part, splitQty > 0 ? splitQty : 100));
                }
            });
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
                if (container.querySelectorAll('.size-row').length > 1) {
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

        parseExistingSizes();
        recalculateTotals();
    });
    </script>
    @endpush
</x-app-layout>
