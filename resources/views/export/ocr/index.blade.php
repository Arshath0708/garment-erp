<x-app-layout>
    <x-slot name="header">Document OCR</x-slot>

    <x-ui.card title="Document OCR — Uploaded docs" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('export.documents.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-files me-1"></i> Export Documents
            </a>
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Separate desk for <strong>Uploaded</strong> sheet rows only (not Generated packing/invoice formats).
            All uploaded checklist types are live for Gemini OCR.
        </p>

        @unless($ocrConfigured)
            <div class="alert alert-warning">
                Gemini is not configured. Add <code>GEMINI_API_KEY</code> to <code>.env</code>, then run
                <code>php artisan config:clear</code>.
            </div>
        @endunless

        @if($documents->isEmpty())
            <x-ui.empty-state icon="bi-stars"
                              title="No Export Document to attach OCR to"
                              message="Raise an Export Document from a confirmed Order Confirmation first, then come back here." />
        @else
            <form id="ocr-save-form" action="{{ route('export.ocr.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf

                <div class="col-md-5">
                    <label class="form-label fw-semibold">Export Document <span class="req">*</span></label>
                    <select name="export_document_id" id="export_document_id" class="form-select" required
                            onchange="window.location='{{ route('export.ocr.index') }}?export_document_id='+this.value+'&type_code={{ $typeCode }}'">
                        @foreach($documents as $doc)
                            <option value="{{ $doc->id }}" @selected($selected?->id === $doc->id)>
                                {{ $doc->doc_num }} — {{ $doc->buyer?->company_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Uploaded document type <span class="req">*</span></label>
                    <select name="type_code" id="type_code" class="form-select" required>
                        @foreach($typeLabels as $code => $label)
                            <option value="{{ $code }}" @selected($typeCode === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">All uploaded types from the export docs sheet are enabled.</div>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Scan / PDF <span class="req">*</span></label>
                    <input type="file" name="file" id="ocr-file" class="form-control" required
                           accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,image/*,application/pdf">
                </div>

                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" id="btn-ocr-extract" class="btn btn-outline-primary"
                            @disabled(! $ocrConfigured)>
                        <i class="bi bi-stars me-1"></i> Extract with Gemini
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save to checklist
                    </button>
                    @if($selected)
                        <a href="{{ route('export.documents.show', $selected) }}" class="btn btn-outline-secondary">
                            Open Export Document
                        </a>
                    @endif
                    <span id="ocr-status" class="small text-body-secondary"></span>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Reference no.</label>
                    <input type="text" name="reference_no" id="ocr-reference" class="form-control"
                           value="{{ old('reference_no', $checklist?->reference_no) }}"
                           placeholder="Filled by OCR — editable">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Remarks</label>
                    <input type="text" name="remarks" id="ocr-remarks" class="form-control"
                           value="{{ old('remarks', $checklist?->remarks) }}"
                           placeholder="Date / SB / CHA notes from OCR">
                </div>

                @if($typeCode === 'insurance')
                    <div class="col-12">
                        <div class="alert alert-light border small mb-0">
                            <strong>Insurance (#16) option 2:</strong> upload the certificate with B/L number and date.
                            To cancel the draft (option 1), use Update on the Export Document checklist.
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">B/L number <span class="req">*</span></label>
                        <input type="text" name="bl_number" id="ocr-bl-number" class="form-control" required
                               value="{{ old('bl_number', $checklist?->insuranceBlNumber()) }}"
                               placeholder="From final B/L">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">B/L date <span class="req">*</span></label>
                        <input type="date" name="bl_date" id="ocr-bl-date" class="form-control" required
                               value="{{ old('bl_date', $checklist?->insuranceBlDate()) }}">
                    </div>
                    <input type="hidden" name="insurance_action" value="upload_certificate">
                @endif

                @if($checklist)
                    <div class="col-12">
                        <div class="small text-body-secondary">
                            Current checklist status:
                            <span class="badge text-bg-{{ $checklist->statusColor() }}">{{ $checklist->statusLabel() }}</span>
                            @if($checklist->hasFile())
                                · <a href="{{ $checklist->fileUrl() }}" target="_blank" rel="noopener">{{ $checklist->original_name }}</a>
                            @endif
                        </div>
                    </div>
                @endif
            </form>

            @if(count($upcomingTypes))
            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-semibold mb-2">Coming next (Uploaded only)</h6>
                <ul class="small text-body-secondary mb-0">
                    @foreach($upcomingTypes as $label)
                        <li>{{ $label }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        @endif
    </x-ui.card>

    {{-- Must stay inside <x-app-layout> so @stack('scripts') in the layout picks it up. --}}
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const btn = document.getElementById('btn-ocr-extract');
        const fileInput = document.getElementById('ocr-file');
        const typeSelect = document.getElementById('type_code');
        const docSelect = document.getElementById('export_document_id');
        const refInput = document.getElementById('ocr-reference');
        const remarksInput = document.getElementById('ocr-remarks');
        const statusEl = document.getElementById('ocr-status');
        if (! btn || ! fileInput || ! statusEl) return;

        // Keep checklist status / saved fields in sync with the selected type.
        if (typeSelect && docSelect) {
            typeSelect.addEventListener('change', function () {
                const base = @json(route('export.ocr.index'));
                window.location = base
                    + '?export_document_id=' + encodeURIComponent(docSelect.value)
                    + '&type_code=' + encodeURIComponent(typeSelect.value);
            });
        }

        function csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('#ocr-save-form input[name="_token"]')?.value
                || '';
        }

        function xsrfToken() {
            const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
            return match ? decodeURIComponent(match[1]) : '';
        }

        btn.addEventListener('click', async function () {
            if (! fileInput.files.length) {
                statusEl.className = 'small text-danger';
                statusEl.textContent = 'Choose a PDF or image first.';
                return;
            }

            const token = csrfToken();
            if (! token) {
                statusEl.className = 'small text-danger';
                statusEl.textContent = 'Missing CSRF token — refresh the page (Ctrl+F5) and try again.';
                return;
            }

            const body = new FormData();
            body.append('file', fileInput.files[0]);
            body.append('type_code', typeSelect.value);
            body.append('_token', token);

            const headers = {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            };
            const xsrf = xsrfToken();
            if (xsrf) {
                headers['X-XSRF-TOKEN'] = xsrf;
            }

            btn.disabled = true;
            statusEl.className = 'small text-body-secondary';
            statusEl.textContent = 'Reading with Gemini…';

            try {
                const res = await fetch(@json(route('export.ocr.extract')), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers,
                    body,
                });
                const data = await res.json().catch(() => ({}));

                if (res.status === 419) {
                    throw new Error('Session expired (CSRF). Refresh the page (Ctrl+F5), re-select the file, then Extract again.');
                }
                if (! res.ok) throw new Error(data.message || ('OCR failed (' + res.status + ')'));

                if (data.reference_no) refInput.value = data.reference_no;
                if (data.remarks) remarksInput.value = data.remarks;

                const blNumberInput = document.getElementById('ocr-bl-number');
                const blDateInput = document.getElementById('ocr-bl-date');
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

                statusEl.className = 'small text-success';
                statusEl.textContent = 'Fields filled — review, then Save to checklist.';
            } catch (err) {
                statusEl.className = 'small text-danger';
                statusEl.textContent = err.message || 'OCR failed.';
            } finally {
                btn.disabled = false;
            }
        });
    });
    </script>
    @endpush
</x-app-layout>
