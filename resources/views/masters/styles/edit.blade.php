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

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Size Range</label>
                        <input type="text" name="sizes" class="form-control" value="{{ old('sizes', $style->sizes) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Batch Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="target_qty" class="form-control" value="{{ old('target_qty', $style->target_qty) }}" required>
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
</x-app-layout>
