<x-app-layout>
    <x-slot name="header">QC CAPA</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-body-secondary small mb-0">
            Corrective actions from in-production QC fails.
            @if(($openCount ?? 0) > 0)
                <strong class="text-warning">{{ $openCount }} open</strong>.
            @endif
        </p>
        <a href="{{ route('manufacturing.index') }}" class="btn btn-sm btn-outline-secondary">Production</a>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="search" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Order, defect, plan">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="open" @selected(($filters['status'] ?? '') === 'open')>Open</option>
                        <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Closed</option>
                        <option value="all" @selected(($filters['status'] ?? '') === 'all')>All</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('manufacturing.capa.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order</th>
                            <th>Stage</th>
                            <th>Defect</th>
                            <th>CAPA plan</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($checks as $check)
                            <tr class="{{ $check->capa_status === 'open' && $check->capa_due_date && $check->capa_due_date->isPast() ? 'table-warning' : '' }}">
                                <td>
                                    @if($check->productionOrder)
                                        <a href="{{ route('manufacturing.edit', $check->productionOrder) }}">{{ $check->productionOrder->order_number }}</a>
                                        <div class="small text-body-secondary">{{ $check->productionOrder->garmentStyle?->style_number }}</div>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $check->stageLabel() }}</td>
                                <td class="small">
                                    <span class="fw-semibold">{{ $check->defectCode?->code ?? '—' }}</span>
                                    <div class="text-body-secondary">{{ $check->defectCode?->name }}</div>
                                    <div>Fail {{ $check->failed_qty }} / {{ $check->checked_qty }}</div>
                                </td>
                                <td class="small" style="max-width:280px">{{ $check->capa_plan ?: '—' }}</td>
                                <td class="small">{{ $check->capa_due_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $check->capa_status === 'open' ? 'warning' : 'secondary' }}">{{ ucfirst($check->capa_status) }}</span>
                                    @if($check->capa_status === 'closed' && $check->capaCloser)
                                        <div class="small text-body-secondary">{{ $check->capaCloser->name }} · {{ $check->capa_closed_at?->format('d M') }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($check->capa_status === 'open')
                                        <form action="{{ route('manufacturing.capa.close', $check) }}" method="POST" class="d-inline-flex gap-1 align-items-center">
                                            @csrf
                                            <input type="text" name="close_note" class="form-control form-control-sm" style="width:140px" placeholder="Close note">
                                            <button class="btn btn-sm btn-outline-success">Close</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-secondary py-4">No CAPA rows. Fail a mid-production QC check with a defect code and fix plan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $checks->links() }}
        </div>
    </div>

    @if(($defectCodes ?? collect())->isNotEmpty())
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Defect code library</h6>
                <p class="small text-body-secondary mb-3">Seeded garment defects. Used when QC fails on the production order.</p>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Category</th></tr></thead>
                        <tbody>
                            @foreach($defectCodes as $code)
                                <tr>
                                    <td class="font-monospace">{{ $code->code }}</td>
                                    <td>{{ $code->name }}</td>
                                    <td>{{ $code->categoryLabel() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
