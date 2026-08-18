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

        {{-- Always-visible status strip — the handful of facts worth seeing without clicking a tab. --}}
        <div class="d-flex flex-wrap align-items-center gap-2 gap-md-3 pb-3 mb-3 border-bottom">
            <span class="badge text-bg-{{ $document->statusColor() }}">{{ $document->statusLabel() }}</span>
            <span class="text-body-secondary small">{{ $document->checklistProgress() }} checklist items complete</span>
            <span class="text-body-secondary small">&middot; {{ $document->buyer?->company_name }} ({{ $document->buyer?->display_code }})</span>
            @if($document->shipment_date)
                <span class="text-body-secondary small">&middot; {{ $document->shipment_date->format('d M Y') }}</span>
            @endif
        </div>

        @php
            $canEdit = auth()->user()->can('export-document.edit');
            $canGenerate = auth()->user()->can('export-document.generate');
            $ocrEnabled = filled(config('services.gemini.key'));
            $ocrTypes = \App\Services\Export\GeminiDocumentExtractor::SUPPORTED_TYPES;

            /**
             * One route per generated document type that actually has a PDF
             * template today — codes missing here still list their checklist
             * rows (so what's pending is visible at a glance) but show "Not
             * built yet" instead of a Generate button. Packing List's single
             * route takes the variant; the rest take none.
             */
            $generatorRoutes = [
                'delivery_challan' => fn ($variant) => route('export.documents.delivery-challan', $document),
                'e_invoice'        => fn ($variant) => route('export.documents.e-invoice', $document),
                'bl_draft'         => fn ($variant) => route('export.documents.bl-draft', $document),
                'packing_list'     => fn ($variant) => route('export.documents.packing-list', [$document, $variant]),
            ];

            /**
             * Section-wise grouping for the Generate Documents tab, mirroring
             * how the shipment paperwork is actually assembled: pack &
             * summarise, invoice & clear customs, hand to the shipping line,
             * settle with the bank/buyer. New generated-category checklist
             * types need only be added to a group here — no other markup
             * changes — to appear with a working Generate button once
             * $generatorRoutes gets an entry for them.
             */
            $generatorGroups = [
                'Packing & Item Docs'        => ['packing_list', 'item_summary'],
                'Invoicing & Customs Filing' => ['export_invoice', 'e_invoice', 'purchase_bills', 'delivery_challan'],
                'Shipping Line'              => ['vgm', 'bl_draft'],
                'Bank & Buyer Docs'          => ['bank_docs', 'buyer_docs'],
            ];

            $generatedEntries = $document->checklist
                ->filter(fn ($e) => $e->type?->category === 'generated')
                ->sortBy('id')
                ->groupBy('type.code');

            $generatedTotal = $generatedEntries->flatten(1)->count();
            $generatedPending = $generatedEntries->flatten(1)->where('status', 'pending')->count();

            $checklistRows = $document->checklist->whereIn('type.category', ['uploaded', 'manual'])->sortBy('id');
            $checklistPending = $checklistRows->where('status', 'pending')->count();
        @endphp

        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button" role="tab">
                    Overview
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-generate" type="button" role="tab">
                    Generate Documents
                    @if($generatedPending)
                        <span class="badge text-bg-secondary ms-1">{{ $generatedPending }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                    Items Shipped
                    @if($document->items->isNotEmpty())
                        <span class="badge text-bg-light text-body-secondary ms-1">{{ $document->items->count() }}</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-checklist" type="button" role="tab">
                    Checklist
                    @if($checklistPending)
                        <span class="badge text-bg-secondary ms-1">{{ $checklistPending }}</span>
                    @endif
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ================= Overview ================= --}}
            <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
                <dl class="row mb-4">
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

                <dl class="row mb-0 pt-3 border-top">
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
            </div>

            {{-- ================= Generate Documents ================= --}}
            <div class="tab-pane fade" id="tab-generate" role="tabpanel">
                <p class="text-body-secondary small mb-3">
                    Every system-generated document for this shipment, one clearly separated section each, in checklist order.
                    Each format inside a section gets its own status and its own Generate button. "Not built yet" means the
                    checklist tracks that format but the system has no template for it yet — record it manually from the
                    Checklist tab in the meantime.
                </p>

                @php
                    $groupLabelByCode = collect($generatorGroups)
                        ->flatMap(fn ($codes, $group) => collect($codes)->mapWithKeys(fn ($code) => [$code => $group]));

                    $groupAccent = [
                        'Packing & Item Docs'        => 'primary',
                        'Invoicing & Customs Filing' => 'info',
                        'Shipping Line'               => 'warning',
                        'Bank & Buyer Docs'          => 'success',
                    ];

                    $typeIcons = [
                        'packing_list'     => 'bi-box2',
                        'item_summary'     => 'bi-list-check',
                        'export_invoice'   => 'bi-file-earmark-spreadsheet',
                        'purchase_bills'   => 'bi-receipt-cutoff',
                        'delivery_challan' => 'bi-truck',
                        'vgm'              => 'bi-speedometer2',
                        'bl_draft'         => 'bi-file-earmark-text',
                        'e_invoice'        => 'bi-receipt',
                        'bank_docs'        => 'bi-bank',
                        'buyer_docs'       => 'bi-envelope-paper',
                    ];

                    $orderedCodes = $generatedEntries
                        ->map(fn ($entries) => $entries->first()->type)
                        ->sortBy('sort_order')
                        ->keys();
                @endphp

                <div class="accordion" id="generateAccordion">
                    @foreach($orderedCodes as $code)
                        @php
                            $entries = $generatedEntries->get($code);
                            $type = $entries->first()->type;
                            $accent = $groupAccent[$groupLabelByCode[$code] ?? ''] ?? 'secondary';
                            $icon = $typeIcons[$code] ?? 'bi-file-earmark';
                            $panelId = 'gen-'.$code;
                        @endphp

                        {{-- No data-bs-parent — each document's panel opens/closes independently, so more than one can stay open at once. --}}
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} border-start border-4 border-{{ $accent }}"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#{{ $panelId }}"
                                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $panelId }}">
                                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 w-100">
                                        <div>
                                            @if($groupLabelByCode[$code] ?? null)
                                                <div class="small text-uppercase text-body-secondary fw-semibold mb-1" style="letter-spacing:.04em;">
                                                    {{ $groupLabelByCode[$code] }}
                                                </div>
                                            @endif
                                            <div class="fs-5 fw-semibold mb-1"><i class="bi {{ $icon }} me-2 text-{{ $accent }}"></i>{{ $type->name }}</div>
                                            @if($type->description)
                                                <div class="text-body-secondary small fw-normal" style="max-width:640px">{{ $type->description }}</div>
                                            @endif
                                        </div>
                                        <span class="badge text-bg-light text-body-secondary flex-shrink-0">
                                            {{ $entries->where('status', 'generated')->count() }} / {{ $entries->count() }} generated
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="{{ $panelId }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        @foreach($entries as $i => $entry)
                                            <div class="col-md-6 col-lg-4">
                                                <div class="border rounded p-3 h-100 d-flex flex-column justify-content-between gap-2">
                                                    <div>
                                                        <div class="fw-semibold small mb-1">
                                                            @if($entry->variantLabel())
                                                                <span class="text-{{ $accent }}">{{ chr(65 + $i) }}.</span> {{ $entry->variantLabel() }}
                                                            @else
                                                                Standard Format
                                                            @endif
                                                        </div>
                                                        <span class="badge text-bg-{{ $entry->statusColor() }}">{{ $entry->statusLabel() }}</span>
                                                        @if($entry->status === 'generated')
                                                            <div class="small text-success mt-1">
                                                                <i class="bi bi-check-circle me-1"></i>{{ $entry->generated_at?->format('d M Y, H:i') }}
                                                                @if($entry->hasFile())
                                                                    &middot; <a href="{{ $entry->fileUrl() }}" target="_blank" rel="noopener">View</a>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        @if($canGenerate && isset($generatorRoutes[$code]))
                                                            <a href="{{ $generatorRoutes[$code]($entry->variant_code) }}"
                                                               class="btn btn-sm btn-outline-primary w-100" target="_blank">
                                                                <i class="bi bi-file-earmark-pdf me-1"></i> Generate
                                                            </a>
                                                        @elseif(! isset($generatorRoutes[$code]))
                                                            <span class="badge text-bg-light text-body-secondary d-block py-2">Not built yet</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ================= Items Shipped ================= --}}
            <div class="tab-pane fade" id="tab-items" role="tabpanel">
                @if($document->items->isNotEmpty())
                    <div class="table-responsive">
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
                @else
                    <p class="text-body-secondary small mb-0">No items on this Export Document yet.</p>
                @endif
            </div>

            {{-- ================= Checklist ================= --}}
            <div class="tab-pane fade" id="tab-checklist" role="tabpanel">
                <p class="text-body-secondary small mb-3">
                    Upload and manual-record rows for this shipment. Uploading the eBRC row closes it. For B/L, LEO,
                    container/seal and a few bank docs, choose a file then use <strong>Extract with AI</strong> to
                    suggest the reference fields before Save. System-generated documents live on the
                    <strong>Generate Documents</strong> tab, not here.
                </p>

                @foreach(['uploaded' => 'Upload & Record Date', 'manual' => 'Manual / Record Only'] as $category => $groupLabel)
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
                                                <details>
                                                    <summary class="small text-primary" style="cursor:pointer">Update</summary>

                                                    @if($entry->type->code === 'insurance')
                                                        <div class="mt-2 small text-body-secondary mb-2">
                                                            Sheet #16 — cancel the draft, or upload the certificate with B/L details.
                                                        </div>

                                                        <form action="{{ route('export.documents.checklist.update', [$document, $entry]) }}"
                                                              method="POST" class="row g-2 mb-3">
                                                            @csrf
                                                            <input type="hidden" name="insurance_action" value="cancel_draft">
                                                            <input type="hidden" name="mark_done" value="1">
                                                            <div class="col-12">
                                                                <button class="btn btn-sm btn-outline-warning" type="submit"
                                                                        onclick="return confirm('Cancel / delete the insurance draft for this shipment?')">
                                                                    <i class="bi bi-x-circle me-1"></i> Option 1 — Cancel draft
                                                                </button>
                                                            </div>
                                                        </form>

                                                        <form action="{{ route('export.documents.checklist.update', [$document, $entry]) }}"
                                                              method="POST" enctype="multipart/form-data"
                                                              class="row g-2 js-checklist-form"
                                                              data-type-code="insurance"
                                                              data-ocr-url="{{ route('export.documents.checklist.ocr', [$document, $entry]) }}"
                                                              data-ocr-supported="1">
                                                            @csrf
                                                            <input type="hidden" name="insurance_action" value="upload_certificate">
                                                            <input type="hidden" name="mark_done" value="1">

                                                            <div class="col-md-4">
                                                                <label class="form-label small mb-0">Certificate file <span class="text-danger">*</span></label>
                                                                <input type="file" name="file" class="form-control form-control-sm js-ocr-file" required
                                                                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,image/*,application/pdf">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small mb-0">B/L number <span class="text-danger">*</span></label>
                                                                <input type="text" name="bl_number" class="form-control form-control-sm"
                                                                       value="{{ old('bl_number', $entry->insuranceBlNumber()) }}" required
                                                                       placeholder="From final B/L">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small mb-0">B/L date <span class="text-danger">*</span></label>
                                                                <input type="date" name="bl_date" class="form-control form-control-sm"
                                                                       value="{{ old('bl_date', $entry->insuranceBlDate()) }}" required>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="form-label small mb-0">Policy / cert no.</label>
                                                                <input type="text" name="reference_no" value="{{ $entry->reference_no }}"
                                                                       class="form-control form-control-sm js-ocr-reference" placeholder="Filled by OCR">
                                                            </div>
                                                            <div class="col-md-5">
                                                                <label class="form-label small mb-0">Remarks</label>
                                                                <input type="text" name="remarks" value="{{ $entry->remarks }}"
                                                                       class="form-control form-control-sm js-ocr-remarks" placeholder="Extra notes">
                                                            </div>
                                                            <div class="col-md-3 d-flex flex-wrap gap-1 align-items-end">
                                                                <button type="button" class="btn btn-sm btn-outline-primary js-ocr-extract"
                                                                        @disabled(! $ocrEnabled)
                                                                        title="{{ $ocrEnabled ? 'Read the certificate with Gemini' : 'Set GEMINI_API_KEY in .env to enable' }}">
                                                                    <i class="bi bi-stars me-1"></i>Extract
                                                                </button>
                                                                <button class="btn btn-sm btn-primary" type="submit">
                                                                    Option 2 — Save certificate
                                                                </button>
                                                                @if($entry->status !== 'pending')
                                                                    <button class="btn btn-sm btn-outline-danger" type="submit"
                                                                            form="reset-checklist-{{ $entry->id }}">Reset</button>
                                                                @endif
                                                            </div>
                                                            <div class="col-12 small text-body-secondary js-ocr-status d-none"></div>
                                                        </form>
                                                    @else
                                                    <form action="{{ route('export.documents.checklist.update', [$document, $entry]) }}"
                                                          method="POST" enctype="multipart/form-data"
                                                          class="row g-2 mt-2 js-checklist-form"
                                                          data-type-code="{{ $entry->type->code }}"
                                                          data-ocr-url="{{ route('export.documents.checklist.ocr', [$document, $entry]) }}"
                                                          data-ocr-supported="{{ in_array($entry->type->code, $ocrTypes, true) ? '1' : '0' }}">
                                                        @csrf
                                                        <input type="hidden" name="mark_done" value="1">

                                                        @if($entry->type->requiresFile())
                                                            <div class="col-md-3">
                                                                <input type="file" name="file" class="form-control form-control-sm js-ocr-file"
                                                                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,image/*,application/pdf">
                                                            </div>
                                                        @endif
                                                        <div class="col-md-3">
                                                            <input type="text" name="reference_no" value="{{ $entry->reference_no }}"
                                                                   class="form-control form-control-sm js-ocr-reference" placeholder="Reference no.">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <input type="text" name="remarks" value="{{ $entry->remarks }}"
                                                                   class="form-control form-control-sm js-ocr-remarks" placeholder="Remarks">
                                                        </div>
                                                        <div class="col-md-3 d-flex flex-wrap gap-1">
                                                            @if($entry->type->requiresFile() && in_array($entry->type->code, $ocrTypes, true))
                                                                <button type="button" class="btn btn-sm btn-outline-primary js-ocr-extract"
                                                                        @disabled(! $ocrEnabled)
                                                                        title="{{ $ocrEnabled ? 'Read the selected file with Gemini' : 'Set GEMINI_API_KEY in .env to enable' }}">
                                                                    <i class="bi bi-stars me-1"></i>Extract with AI
                                                                </button>
                                                            @endif
                                                            <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                                            @if($entry->status !== 'pending')
                                                                <button class="btn btn-sm btn-outline-danger" type="submit"
                                                                        form="reset-checklist-{{ $entry->id }}">Reset</button>
                                                            @endif
                                                        </div>
                                                        <div class="col-12 small text-body-secondary js-ocr-status d-none"></div>
                                                    </form>
                                                    @endif

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
            </div>
        </div>
    </x-ui.card>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-checklist-form').forEach(function (form) {
            const btn = form.querySelector('.js-ocr-extract');
            if (! btn) return;

            const fileInput = form.querySelector('.js-ocr-file');
            const refInput = form.querySelector('.js-ocr-reference');
            const remarksInput = form.querySelector('.js-ocr-remarks');
            const statusEl = form.querySelector('.js-ocr-status');

            btn.addEventListener('click', async function () {
                if (! fileInput || ! fileInput.files.length) {
                    statusEl.classList.remove('d-none', 'text-success');
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = 'Choose a PDF or image first.';
                    return;
                }

                const csrf = form.querySelector('input[name="_token"]')?.value
                    || document.querySelector('meta[name="csrf-token"]')?.content;

                const xsrfMatch = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
                const xsrf = xsrfMatch ? decodeURIComponent(xsrfMatch[1]) : '';

                const body = new FormData();
                body.append('file', fileInput.files[0]);
                body.append('type_code', form.dataset.typeCode);
                body.append('_token', csrf);

                btn.disabled = true;
                statusEl.classList.remove('d-none', 'text-danger', 'text-success');
                statusEl.classList.add('text-body-secondary');
                statusEl.textContent = 'Reading document with Gemini…';

                try {
                    const headers = {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                    if (csrf) headers['X-CSRF-TOKEN'] = csrf;
                    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;

                    const res = await fetch(form.dataset.ocrUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers,
                        body,
                    });

                    const data = await res.json().catch(function () { return {}; });

                    if (res.status === 419) {
                        throw new Error('Session expired (CSRF). Refresh the page (Ctrl+F5) and try again.');
                    }
                    if (! res.ok) {
                        throw new Error(data.message || ('OCR failed (' + res.status + ')'));
                    }

                    if (data.reference_no != null && data.reference_no !== '') {
                        refInput.value = data.reference_no;
                    }
                    if (data.remarks != null && data.remarks !== '') {
                        remarksInput.value = data.remarks;
                    }

                    const blNumberInput = form.querySelector('[name="bl_number"]');
                    const blDateInput = form.querySelector('[name="bl_date"]');
                    const fields = data.fields || {};
                    if (blNumberInput && fields.bl_number) {
                        blNumberInput.value = fields.bl_number;
                    }
                    if (blDateInput) {
                        const blDate = fields.bl_date || fields.document_date
                            || (String(data.remarks || '').match(/B\/L date:\s*(\d{4}-\d{2}-\d{2})/i) || [])[1]
                            || (String(data.remarks || '').match(/Date:\s*(\d{4}-\d{2}-\d{2})/i) || [])[1]
                            || '';
                        if (blDate) blDateInput.value = blDate;
                    }

                    statusEl.classList.remove('text-body-secondary', 'text-danger');
                    statusEl.classList.add('text-success');
                    statusEl.textContent = 'Fields filled — review them, then click Save.';
                } catch (err) {
                    statusEl.classList.remove('text-body-secondary', 'text-success');
                    statusEl.classList.add('text-danger');
                    statusEl.textContent = err.message || 'OCR failed.';
                } finally {
                    btn.disabled = false;
                }
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
