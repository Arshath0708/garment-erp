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
            <strong>Phase 1:</strong> #4 Checklist from CHA. After you test this, we unlock the next uploaded types.
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
                    <div class="form-text">Phase 1 — only #4 is enabled.</div>
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

            <div class="mt-4 pt-3 border-top">
                <h6 class="fw-semibold mb-2">Coming next (Uploaded only)</h6>
                <ul class="small text-body-secondary mb-0">
                    @foreach($upcomingTypes as $label)
                        <li>{{ $label }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </x-ui.card>
</x-app-layout>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btn-ocr-extract');
    const fileInput = document.getElementById('ocr-file');
    const typeSelect = document.getElementById('type_code');
    const refInput = document.getElementById('ocr-reference');
    const remarksInput = document.getElementById('ocr-remarks');
    const statusEl = document.getElementById('ocr-status');
    if (! btn || ! fileInput) return;

    btn.addEventListener('click', async function () {
        if (! fileInput.files.length) {
            statusEl.className = 'small text-danger';
            statusEl.textContent = 'Choose a PDF or image first.';
            return;
        }

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;

        const body = new FormData();
        body.append('file', fileInput.files[0]);
        body.append('type_code', typeSelect.value);
        body.append('_token', csrf);

        btn.disabled = true;
        statusEl.className = 'small text-body-secondary';
        statusEl.textContent = 'Reading with Gemini…';

        try {
            const res = await fetch(@json(route('export.ocr.extract')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const data = await res.json().catch(() => ({}));
            if (! res.ok) throw new Error(data.message || ('OCR failed (' + res.status + ')'));

            if (data.reference_no) refInput.value = data.reference_no;
            if (data.remarks) remarksInput.value = data.remarks;

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
