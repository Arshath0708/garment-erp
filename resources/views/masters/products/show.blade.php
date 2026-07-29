<x-app-layout>
    <x-slot name="header">Product Details</x-slot>

    <x-ui.card title="{{ $product->item_group_code }} — {{ $product->name }}" variant="primary">
        <x-slot name="actions">
            @can('product.edit')
                <a href="{{ route('masters.products.edit', $product) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.products.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <div class="row">
            <div class="col-lg-6">
                <h6 class="text-uppercase text-body-secondary fw-bold small border-bottom pb-2 mb-3">Identification</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Item Group Code</dt>
                    <dd class="col-sm-7"><span class="badge text-bg-light border font-monospace">{{ $product->item_group_code }}</span></dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Category</dt>
                    <dd class="col-sm-7">{{ $product->category?->name ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Name on Export Doc</dt>
                    <dd class="col-sm-7">{{ $product->name_on_export_document ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Barcode</dt>
                    <dd class="col-sm-7 font-monospace">{{ $product->barcode ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Status</dt>
                    <dd class="col-sm-7"><x-ui.status-badge :status="$product->status === 'active'" /></dd>
                </dl>
            </div>

            <div class="col-lg-6">
                <h6 class="text-uppercase text-body-secondary fw-bold small border-bottom pb-2 mb-3">Units &amp; Classification</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Unit (PO &amp; OC)</dt>
                    <dd class="col-sm-7">{{ $product->unit_po ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Unit (Export Docs)</dt>
                    <dd class="col-sm-7">{{ $product->unit_export ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">HSN Code</dt>
                    <dd class="col-sm-7 font-monospace">{{ $product->hsn_code ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Drawback Sr. No.</dt>
                    <dd class="col-sm-7">{{ $product->drawback_sr_no ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Price Band</dt>
                    <dd class="col-sm-7">{{ $product->priceBand?->label ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">GST %</dt>
                    <dd class="col-sm-7">{{ $product->gstRate?->label ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <h6 class="text-uppercase text-body-secondary fw-bold small border-bottom pb-2 mb-3">Export Incentives</h6>
        @if($product->incentives->isEmpty())
            <p class="text-body-secondary small">No incentive schemes recorded for this product.</p>
        @else
            <div class="table-responsive mb-4">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Scheme</th>
                            <th class="text-end">Rate %</th>
                            <th class="text-end">Rate % 2</th>
                            <th class="text-end">Cap Value</th>
                            <th>Calculated On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->incentives as $incentive)
                            <tr>
                                <td class="fw-semibold">{{ $incentive->schemeLabel() }}</td>
                                <td class="text-end">{{ $incentive->percent_1 ?? '—' }}</td>
                                <td class="text-end">{{ $incentive->percent_2 ?? '—' }}</td>
                                <td class="text-end">{{ $incentive->cap_value ?? '—' }}</td>
                                <td class="text-body-secondary">{{ $incentive->calculationBasis?->name ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-6">
                <h6 class="text-uppercase text-body-secondary fw-bold small border-bottom pb-2 mb-3">Fabric Measurement</h6>
                <dl class="row mb-4">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Fabric Length (mtrs)</dt>
                    <dd class="col-sm-7">{{ $product->fabric_length_mtr ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Fabric Width (inch)</dt>
                    <dd class="col-sm-7">{{ $product->fabric_width_inch ?? '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Sq. Mtrs / Unit</dt>
                    <dd class="col-sm-7 fw-semibold">{{ $product->sq_mtr_per_unit ?? '—' }}</dd>
                </dl>
            </div>

            <div class="col-lg-6">
                <h6 class="text-uppercase text-body-secondary fw-bold small border-bottom pb-2 mb-3">Other</h6>
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Description</dt>
                    <dd class="col-sm-7">{{ $product->description ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Remarks</dt>
                    <dd class="col-sm-7">{{ $product->remarks ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Created</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $product->created_at?->format('d M Y, H:i') }}
                        @if($product->creator) by {{ $product->creator->name }} @endif
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Last updated</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $product->updated_at?->format('d M Y, H:i') }}
                        @if($product->updater) by {{ $product->updater->name }} @endif
                    </dd>
                </dl>
            </div>
        </div>
    </x-ui.card>
</x-app-layout>
