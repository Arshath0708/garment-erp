<x-app-layout>
    <x-slot name="header">Export Document {{ $document->doc_num }}</x-slot>

    <x-ui.card :title="$document->doc_num" variant="primary">
        <x-slot name="actions">
            @can('export-document.edit')
                <a href="{{ route('export.documents.edit', $document) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('export.documents.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9">
                <span class="badge text-bg-{{ $document->statusColor() }}">{{ $document->statusLabel() }}</span>
                <span class="text-body-secondary small ms-2">{{ $document->checklistProgress() }} checklist items complete</span>
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Order Confirmation</dt>
            <dd class="col-sm-9">
                @if($document->orderConfirmation)
                    <a href="{{ route('sales.order-confirmations.show', $document->orderConfirmation) }}">{{ $document->orderConfirmation->oc_num }}</a>
                @else — @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Buyer</dt>
            <dd class="col-sm-9">{{ $document->buyer?->company_name }} ({{ $document->buyer?->display_code }})</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Currency / Incoterm</dt>
            <dd class="col-sm-9">{{ $document->currency?->iso_code ?? '—' }} / {{ $document->incoterm?->code ?? '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Shipment</dt>
            <dd class="col-sm-9">
                {{ $document->shipmentMethod?->name ?? '—' }}
                @if($document->shipment_date) &middot; {{ $document->shipment_date->format('d M Y') }} @endif
                @if($document->portOfLoading) &middot; POL: {{ $document->portOfLoading->name }} @endif
                @if($document->portOfDischarge) &middot; POD: {{ $document->portOfDischarge->name }} @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Remarks</dt>
            <dd class="col-sm-9">{{ $document->remarks ?: '—' }}</dd>
        </dl>

        {{--
            Generate Documents — a clean, dedicated panel for the document
            types the system can actually produce a PDF for today. Was
            previously one link buried inside each checklist row's
            collapsed "Update" details — easy to miss, and mixed in among
            rows that are upload/manual-only and have no button to find.
            Two entries only: those are the only two of the ten
            "system-generated" checklist types (see DocumentChecklistType)
            with a real template behind them. The other eight are tracked
            in the checklist below same as before; they simply have no
            Generate button because nothing yet generates them.
        --}}
        @php
            $generators = [
                'delivery_challan' => [
                    'label' => 'Delivery Challan',
                    'icon'  => 'bi-truck',
                    'desc'  => 'Sent to customs when the goods leave for the docks / airport.',
                ],
                'e_invoice' => [
                    'label' => 'E-Invoice',
                    'icon'  => 'bi-receipt',
                    'desc'  => 'The export invoice, ready to upload to the GST e-Invoice portal.',
                ],
            ];
            $canGenerate = auth()->user()->can('export-document.generate');
        @endphp

        <h6 class="fw-semibold mb-2">Generate Documents</h6>
        <div class="row g-3 mb-4">
            @foreach($generators as $code => $meta)
                @php $entry = $document->checklist->firstWhere('type.code', $code); @endphp
                <div class="col-md-6">
                    <div class="border rounded p-3 d-flex align-items-start justify-content-between gap-3 h-100">
                        <div>
                            <div class="fw-semibold"><i class="bi {{ $meta['icon'] }} me-1"></i>{{ $meta['label'] }}</div>
                            <div class="small text-body-secondary">{{ $meta['desc'] }}</div>
                            @if($entry?->status === 'generated')
                                <div class="small text-success mt-1">
                                    <i class="bi bi-check-circle me-1"></i>Last generated {{ $entry->generated_at?->format('d M Y, H:i') }}
                                </div>
                            @endif
                        </div>
                        @if($canGenerate)
                            <a href="{{ route('export.documents.'.str_replace('_', '-', $code), $document) }}"
                               class="btn btn-sm btn-primary flex-shrink-0" target="_blank">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Generate
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if($document->items->isNotEmpty())
            <h6 class="fw-semibold mb-2">Items Shipped</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Design No.</th>
                            <th>Product</th>
                            <th>Colour / Size</th>
                            <th>Unit</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($document->items as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">{{ $item->design_no ?: '—' }}</td>
                                <td>{{ $item->product?->name ?? '—' }}</td>
                                <td>
                                    @forelse($item->colours as $colour)
                                        <div class="small mb-1">
                                            @if($colour->colour)<span class="badge text-bg-light border me-1">{{ $colour->colour }}</span>@endif
                                            @forelse($colour->sizes as $size)
                                                <span class="text-body-secondary">{{ $size->size }}:{{ $size->qty }}</span>@if(! $loop->last), @endif
                                            @empty
                                                <span class="text-body-secondary">—</span>
                                            @endforelse
                                        </div>
                                    @empty
                                        —
                                    @endforelse
                                </td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td class="text-end">{{ $item->qty }}</td>
                                <td class="text-end">{{ number_format((float) $item->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="fw-semibold table-light">
                            <td colspan="6" class="text-end">Total</td>
                            <td class="text-end">{{ number_format($document->totalAmount(), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif

        <h6 class="fw-semibold mb-2">Document Checklist</h6>
        <p class="text-body-secondary small mb-3">
            The full "Export docs to payment receipt from buyer" checklist for this shipment. Uploading the eBRC row closes it.
        </p>

        @php $canEdit = auth()->user()->can('export-document.edit'); @endphp

        @foreach(['generated' => 'Generated by the System', 'uploaded' => 'Upload & Record Date', 'manual' => 'Manual / Record Only'] as $category => $groupLabel)
            @php $rows = $document->checklist->where('type.category', $category)->sortBy('id'); @endphp
            @continue($rows->isEmpty())

            <h6 class="text-body-secondary small text-uppercase mt-4 mb-2">{{ $groupLabel }}</h6>
            <div class="table-responsive mb-2">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:28%">Document</th>
                            <th style="width:12%">Status</th>
                            <th style="width:18%">File / Reference</th>
                            <th style="width:14%">Date</th>
                            @if($canEdit)
                                <th>Update</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $entry)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $entry->type->name }}</div>
                                    @if($entry->variantLabel())
                                        <div class="small text-body-secondary">{{ $entry->variantLabel() }}</div>
                                    @endif
                                    @if($entry->type->description)
                                        <div class="small text-body-secondary">{{ $entry->type->description }}</div>
                                    @endif
                                </td>
                                <td><span class="badge text-bg-{{ $entry->statusColor() }}">{{ $entry->statusLabel() }}</span></td>
                                <td class="small">
                                    @if($entry->hasFile())
                                        <a href="{{ $entry->fileUrl() }}" target="_blank" rel="noopener">
                                            <i class="bi bi-paperclip me-1"></i>{{ $entry->original_name ?? 'File' }}
                                        </a><br>
                                    @endif
                                    {{ $entry->reference_no ?: ($entry->hasFile() ? '' : '—') }}
                                </td>
                                <td class="small">{{ ($entry->uploaded_at ?? $entry->generated_at)?->format('d M Y') ?? '—' }}</td>
                                @if($canEdit)
                                    <td>
                                        {{-- Generating lives in the "Generate Documents" panel above now — one
                                             button per document, not one hidden per checklist row. --}}
                                        <details>
                                            <summary class="small text-primary" style="cursor:pointer">Update</summary>

                                            <form action="{{ route('export.documents.checklist.update', [$document, $entry]) }}"
                                                  method="POST" enctype="multipart/form-data" class="row g-2 mt-2">
                                                @csrf
                                                <input type="hidden" name="mark_done" value="1">

                                                @if($entry->type->requiresFile())
                                                    <div class="col-md-3">
                                                        <input type="file" name="file" class="form-control form-control-sm">
                                                    </div>
                                                @endif
                                                <div class="col-md-3">
                                                    <input type="text" name="reference_no" value="{{ $entry->reference_no }}"
                                                           class="form-control form-control-sm" placeholder="Reference no.">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" name="remarks" value="{{ $entry->remarks }}"
                                                           class="form-control form-control-sm" placeholder="Remarks">
                                                </div>
                                                <div class="col-md-3 d-flex gap-1">
                                                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                                    @if($entry->status !== 'pending')
                                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                                                form="reset-checklist-{{ $entry->id }}">Reset</button>
                                                    @endif
                                                </div>
                                            </form>

                                            @if($entry->status !== 'pending')
                                                <form id="reset-checklist-{{ $entry->id }}"
                                                      action="{{ route('export.documents.checklist.reset', [$document, $entry]) }}"
                                                      method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        </details>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <dl class="row mb-0 mt-4 pt-3 border-top">
            <dt class="col-sm-3 text-body-secondary fw-normal">Created</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $document->created_at?->format('d M Y, H:i') }}
                @if($document->creator) by {{ $document->creator->name }} @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Last updated</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $document->updated_at?->format('d M Y, H:i') }}
                @if($document->updater) by {{ $document->updater->name }} @endif
            </dd>
        </dl>
    </x-ui.card>
</x-app-layout>
