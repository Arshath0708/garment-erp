<x-app-layout>
    <x-slot name="header">FOB Values</x-slot>

    <x-ui.card title="FOB Value Master" variant="primary">
        <x-slot name="actions">
            @can('fob-value.create')
                <a href="{{ route('masters.fob-values.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Add FOB Value
                </a>
            @endcan
        </x-slot>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small text-body-secondary mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="Name or remarks">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('masters.fob-values.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Name</th>
                        <th>Remarks</th>
                        <th style="width:110px">Status</th>
                        <th class="text-end" style="width:150px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($fobValues as $fobValue)
                        <tr>
                            <td class="text-body-secondary">{{ $loop->iteration + ($fobValues->currentPage() - 1) * $fobValues->perPage() }}</td>
                            <td class="fw-semibold">{{ $fobValue->name }}</td>
                            <td class="text-body-secondary">{{ $fobValue->remarks ?? '—' }}</td>
                            <td>
                                @can('fob-value.edit')
                                    <form action="{{ route('masters.fob-values.toggle-status', $fobValue) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm p-0 border-0 bg-transparent"
                                                data-bs-toggle="tooltip" title="Click to toggle">
                                            <x-ui.status-badge :status="$fobValue->status === 'active'" />
                                        </button>
                                    </form>
                                @else
                                    <x-ui.status-badge :status="$fobValue->status === 'active'" />
                                @endcan
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @can('fob-value.view')
                                        <a href="{{ route('masters.fob-values.show', $fobValue) }}"
                                           class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endcan
                                    @can('fob-value.edit')
                                        <a href="{{ route('masters.fob-values.edit', $fobValue) }}"
                                           class="btn btn-outline-primary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcan
                                    @can('fob-value.delete')
                                        <x-ui.delete-form
                                            :action="route('masters.fob-values.destroy', $fobValue)"
                                            :confirm="'Delete FOB Value &quot;'.$fobValue->name.'&quot;?'" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state :colspan="5" icon="bi-currency-dollar"
                                          title="No FOB Values yet"
                                          message="Add the first FOB Value entry." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($fobValues->hasPages())
            <div class="mt-3">{{ $fobValues->links('pagination::bootstrap-5') }}</div>
        @endif

        <div class="text-body-secondary small mt-2">
            Showing {{ $fobValues->firstItem() ?? 0 }}–{{ $fobValues->lastItem() ?? 0 }} of {{ $fobValues->total() }}
        </div>
    </x-ui.card>
</x-app-layout>
