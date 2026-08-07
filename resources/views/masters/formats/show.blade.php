<x-app-layout>
    <x-slot name="header">Order Format</x-slot>

    <x-ui.card title="{{ $format->name }}" variant="primary">
        <x-slot name="actions">
            @can('po-format.edit')
                <a href="{{ route('masters.formats.edit', $format) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.formats.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Format Name</dt>
            <dd class="col-sm-9 fw-semibold">{{ $format->name }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Description</dt>
            <dd class="col-sm-9">{{ $format->description ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9"><x-ui.status-badge :status="$format->status === 'active'" /></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Multiple colour rows</dt>
            <dd class="col-sm-9">
                <span class="badge {{ $format->allow_multiple_colours ? 'text-bg-primary' : 'text-bg-light border' }}">
                    {{ $format->allow_multiple_colours ? 'On' : 'Off' }}
                </span>
                <div class="small text-body-secondary mt-1">
                    {{ $format->allow_multiple_colours
                        ? 'Each item row gets a + Add colour button. Colour rows share the same Design, Product, Supplier, FOB and size breakdown.'
                        : 'One colour per item row.' }}
                </div>
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Units</dt>
            <dd class="col-sm-9">
                @forelse($format->units as $unit)
                    <span class="badge text-bg-light border font-monospace me-1 mb-1">{{ $unit->name }}</span>
                @empty
                    —
                @endforelse
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Linked categories</dt>
            <dd class="col-sm-9">
                @forelse($format->categories as $category)
                    <span class="badge text-bg-light border me-1 mb-1">{{ $category->name }}</span>
                @empty
                    <span class="text-body-secondary">Not linked to any category yet — link it from the Category Master.</span>
                @endforelse
            </dd>
        </dl>

        {{-- Shown as the table it produces, not as a list of column rows. The
             point of a format is the shape it comes out in. --}}
        @php
            $sizeColumn = $format->columns->firstWhere('key', 'size');
            $sizeTags   = $sizeColumn->sub_columns ?? [];
        @endphp

        <h6 class="fw-semibold mb-2">Item table</h6>
        <p class="text-body-secondary small">
            Sr. No., Qty and Amount are always drawn. Print-only columns are marked; a red
            <span class="text-danger">*</span> marks a mandatory column.
        </p>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Sr. No.</th>
                        @foreach($format->printColumns() as $column)
                            @if($column->key === 'size' && filled($sizeTags))
                                @foreach($sizeTags as $tag)
                                    <th class="text-center font-monospace">{{ $tag }}</th>
                                @endforeach
                                <th class="text-center fw-semibold">Total</th>
                            @else
                                <th @class(['text-body-secondary fst-italic' => $column->print_only])>
                                    {{ $column->key === 'price' ? $format->priceLabel() : $column->label }}
                                    @if($column->is_mandatory)
                                        <span class="text-danger">*</span>
                                    @endif
                                    @if($column->print_only)
                                        <span class="badge text-bg-light border fw-normal ms-1">print only</span>
                                    @endif
                                </th>
                            @endif
                            @if($column->key === 'unit' && blank($sizeTags))
                                <th>Qty</th>
                            @endif
                        @endforeach
                        <th>Amount</th>
                    </tr>
                </thead>
            </table>
        </div>

        @if(filled($sizeTags))
            <p class="small text-body-secondary">
                Size sub-columns: {{ implode(', ', $sizeTags) }} — every item row gets a fixed qty box per tag
                instead of free-form size entry.
            </p>
        @endif

        @if($format->columns->where('is_enabled', false)->isNotEmpty())
            <p class="small text-body-secondary">
                Switched off:
                {{ $format->columns->where('is_enabled', false)->pluck('label')->implode(', ') }}
            </p>
        @endif

        <div class="row g-4">
            <div class="col-lg-6">
                <h6 class="fw-semibold mb-2">Delivery details</h6>
                <div class="border rounded p-3 bg-body-tertiary small" style="min-height:5rem">
                    {{ $format->delivery_details ?: 'None recorded.' }}
                </div>
            </div>
            <div class="col-lg-6">
                <h6 class="fw-semibold mb-2">Packing details</h6>
                <div class="border rounded p-3 bg-body-tertiary small" style="min-height:5rem">
                    {{ $format->packing_details ?: 'None recorded.' }}
                </div>
            </div>
        </div>

        @if($format->images->isNotEmpty())
            <h6 class="fw-semibold mt-4 mb-2">
                Reference images
                <span class="text-body-secondary fw-normal small">— printed on PDF / Excel export only</span>
            </h6>
            <div class="d-flex flex-wrap gap-3">
                @foreach($format->images as $image)
                    <a href="{{ $image->url }}" target="_blank" rel="noopener" class="reference-image">
                        <img src="{{ $image->url }}" alt="{{ $image->original_name }}">
                    </a>
                @endforeach
            </div>
        @endif

        <dl class="row mb-0 mt-4 pt-3 border-top">
            <dt class="col-sm-3 text-body-secondary fw-normal">Created</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $format->created_at?->format('d M Y, H:i') }}
                @if($format->creator) by {{ $format->creator->name }} @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Last updated</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $format->updated_at?->format('d M Y, H:i') }}
                @if($format->updater) by {{ $format->updater->name }} @endif
            </dd>
        </dl>
    </x-ui.card>
</x-app-layout>
