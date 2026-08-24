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

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Size Range</label>
                        <input type="text" name="sizes" class="form-control" placeholder="e.g. S, M, L, XL, XXL" value="{{ old('sizes', 'S, M, L, XL, XXL') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Batch Quantity (pcs) <span class="text-danger">*</span></label>
                        <input type="number" name="target_qty" class="form-control" placeholder="e.g. 10000" value="{{ old('target_qty', 10000) }}" required>
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
</x-app-layout>
