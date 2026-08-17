<x-app-layout>
    <x-slot name="header">Export Document {{ $document->doc_num }}</x-slot>

    <x-ui.card :title="$document->doc_num" variant="primary">
        <x-slot name="actions">
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
            For B/L, LEO, container/seal and a few bank docs, choose a file then use <strong>Extract with AI</strong> to suggest the reference fields before Save.
        </p>

        @php
            $canEdit = auth()->user()->can('export-document.edit');
            $ocrEnabled = filled(config('services.gemini.key'));
            $ocrTypes = \App\Services\Export\GeminiDocumentExtractor::SUPPORTED_TYPES;
        @endphp

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
