<x-app-layout>
    <x-slot name="header">Style Costing</x-slot>

    <x-ui.card title="Style Costing" variant="primary">
        <x-slot name="actions">
            @can('style-costing.create')
                <a href="{{ route('style-costings.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Costing
                </a>
            @endcan
        </x-slot>

        <p class="text-body-secondary small mb-3">
            BOM qty × rate, plus cut-make. <strong>Approve</strong> signs “this style costs ₹X per piece.”
            Draft sheets can still be edited.
        </p>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small text-body-secondary mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="CS no., style">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\StyleCosting::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('style-costings.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Style</th>
                        <th>Buyer</th>
                        <th>Date</th>
                        <th class="text-end">₹ / pc</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costings as $costing)
                        <tr>
                            <td class="font-monospace">{{ $costing->costing_num }}</td>
                            <td>{{ $costing->garmentStyle?->style_number }} — {{ $costing->garmentStyle?->name }}</td>
                            <td>{{ $costing->buyer?->company_name ?? '—' }}</td>
                            <td>{{ $costing->costing_date?->format('d M Y') }}</td>
                            <td class="text-end fw-semibold">{{ number_format((float) $costing->total_cost_per_pc, 2) }}</td>
                            <td>
                                <span class="badge text-bg-{{ $costing->statusColor() }}">{{ $costing->statusLabel() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('style-costings.show', $costing) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('style-costing.edit')
                                    @if($costing->isDraft())
                                        <a href="{{ route('style-costings.edit', $costing) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state colspan="7"
                                          icon="bi-calculator"
                                          title="No costing sheets yet"
                                          message="Pick a style with a BOM, fill ₹ rates, then approve." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($costings->hasPages())
            <div class="mt-3">{{ $costings->links() }}</div>
        @endif
    </x-ui.card>
</x-app-layout>
