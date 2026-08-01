<x-app-layout>
    <x-slot name="header">Category Details</x-slot>

    <x-ui.card title="{{ $category->code }} — {{ $category->name }}" variant="primary">
        <x-slot name="actions">
            @can('category.edit')
                <a href="{{ route('masters.categories.edit', $category) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.categories.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <dl class="row mb-0">
            <dt class="col-sm-3 text-body-secondary fw-normal">Category Code</dt>
            <dd class="col-sm-9"><span class="badge text-bg-light border font-monospace">{{ $category->code }}</span></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Category Name</dt>
            <dd class="col-sm-9 fw-semibold">{{ $category->name }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Order Formats Linked</dt>
            <dd class="col-sm-9">
                @forelse($category->formats as $format)
                    <span class="badge text-bg-light border me-1 mb-1">{{ $format->name }}</span>
                @empty
                    —
                @endforelse
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9"><x-ui.status-badge :status="$category->status === 'active'" /></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Products</dt>
            <dd class="col-sm-9">
                {{ $category->products_count }}
                @if($category->products_count > 0)
                    @can('product.view')
                        <a href="{{ route('masters.products.index', ['category_id' => $category->id]) }}"
                           class="small ms-1">View</a>
                    @endcan
                @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Remarks</dt>
            <dd class="col-sm-9">{{ $category->remarks ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Created</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $category->created_at?->format('d M Y, H:i') }}
                @if($category->creator) by {{ $category->creator->name }} @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Last updated</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $category->updated_at?->format('d M Y, H:i') }}
                @if($category->updater) by {{ $category->updater->name }} @endif
            </dd>
        </dl>
    </x-ui.card>
</x-app-layout>
