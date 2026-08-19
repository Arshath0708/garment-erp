<x-app-layout>
    <x-slot name="header">Packing — {{ $document->doc_num }}</x-slot>

    <x-ui.card :title="$document->doc_num" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('export.documents.edit', $document) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil me-1"></i> Edit cartons
            </a>
            <a href="{{ route('export.packing.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Buyer</dt>
            <dd class="col-sm-9">{{ $document->buyer?->company_name }} ({{ $document->buyer?->display_code }})</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Total cartons</dt>
            <dd class="col-sm-9">{{ $document->cartons->count() ?: ($document->total_cartons ?: 0) }} {{ $document->package_kind ?: 'CARTONS' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Net / Gross</dt>
            <dd class="col-sm-9">
                {{ $document->net_weight ? number_format((float) $document->net_weight, 3).' kg' : '—' }}
                /
                {{ $document->gross_weight ? number_format((float) $document->gross_weight, 3).' kg' : '—' }}
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Marks &amp; Nos.</dt>
            <dd class="col-sm-9">{{ $document->marks_and_numbers ?: '—' }}</dd>
        </dl>

        <h6 class="text-body-secondary small text-uppercase mb-2">Generate Packing Lists</h6>
        <div class="row g-3 mb-4">
            @forelse($packingRows as $i => $entry)
                <div class="col-md-4">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold small mb-1">
                            {{ chr(65 + $i) }}. {{ $entry->variantLabel() ?? 'Standard Format' }}
                        </div>
                        <span class="badge text-bg-{{ $entry->statusColor() }}">{{ $entry->statusLabel() }}</span>
                        @can('export-document.generate')
                            <a href="{{ route('export.documents.packing-list', [$document, $entry->variant_code]) }}"
                               class="btn btn-sm btn-outline-primary w-100 mt-2" target="_blank">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Generate
                            </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-12 text-body-secondary small">No packing-list checklist rows on this shipment yet.</div>
            @endforelse
        </div>

        <h6 class="text-body-secondary small text-uppercase mb-2">Cartons</h6>
        @if($document->cartons->isNotEmpty())
            @foreach($document->cartons as $carton)
                <div class="border rounded p-3 mb-2">
                    <div class="d-flex justify-content-between">
                        <strong>Carton {{ $carton->carton_no ?: $loop->iteration }}</strong>
                        <span class="small text-body-secondary">
                            Net {{ $carton->net_weight ? number_format((float) $carton->net_weight, 3) : '—' }} kg
                            &middot; Gross {{ $carton->gross_weight ? number_format((float) $carton->gross_weight, 3) : '—' }} kg
                            @if($carton->dimensions) &middot; {{ $carton->dimensions }} @endif
                        </span>
                    </div>
                    @if($carton->lines->isNotEmpty())
                        <table class="table table-sm mb-0 mt-2">
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="text-end">Qty</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($carton->lines as $line)
                                    <tr>
                                        <td>{{ $line->description ?: '—' }}</td>
                                        <td class="text-end">{{ $line->qty }}</td>
                                        <td>{{ $line->unit ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="small text-body-secondary mt-2">No lines recorded in this carton yet.</div>
                    @endif
                </div>
            @endforeach
        @else
            <p class="text-body-secondary small mb-0">
                No cartons recorded yet. Use <a href="{{ route('export.documents.edit', $document) }}">Edit cartons</a>
                and add carton rows, then generate Packing List formats B and C.
            </p>
        @endif
    </x-ui.card>
</x-app-layout>
