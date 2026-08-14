<x-app-layout>
    <x-slot name="header">Export Documents</x-slot>

    <x-ui.card title="Export Documents" variant="primary">
        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label small text-body-secondary mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Doc no., OC no., buyer">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Buyer</label>
                <select name="buyer_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($buyers as $id => $label)
                        <option value="{{ $id }}" @selected((string) ($filters['buyer_id'] ?? '') === (string) $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('export.documents.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Doc No.</th>
                        <th>OC No.</th>
                        <th>Buyer</th>
                        <th>Shipment Date</th>
                        <th style="width:140px">Checklist</th>
                        <th style="width:120px">Status</th>
                        <th class="text-end" style="width:90px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td class="fw-semibold font-monospace">{{ $document->doc_num }}</td>
                            <td>{{ $document->orderConfirmation?->oc_num ?? '—' }}</td>
                            <td>{{ $document->buyer?->company_name }} <span class="text-body-secondary">({{ $document->buyer?->display_code }})</span></td>
                            <td>{{ $document->shipment_date?->format('d M Y') ?? '—' }}</td>
                            <td>{{ $document->checklistProgress() }}</td>
                            <td><span class="badge text-bg-{{ $document->statusColor() }}">{{ $document->statusLabel() }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('export.documents.show', $document) }}"
                                   class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state :colspan="7" icon="bi-files"
                                          title="No Export Documents yet"
                                          message="Raise one from a confirmed Order Confirmation's item list." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="mt-3">{{ $documents->links('pagination::bootstrap-5') }}</div>
        @endif

        <div class="text-body-secondary small mt-2">
            Showing {{ $documents->firstItem() ?? 0 }}–{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() }}
        </div>
    </x-ui.card>
</x-app-layout>
