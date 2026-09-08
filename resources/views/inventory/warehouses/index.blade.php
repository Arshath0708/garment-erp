<x-app-layout>
    <x-slot name="header">Godowns</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-body-secondary small mb-0">Physical stores / warehouses. Stock lots sit in a godown.</p>
        @can('warehouse.create')
            <a href="{{ route('inventory.warehouses.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add godown</a>
        @endcan
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="text-center">Lots</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($warehouses as $wh)
                            <tr>
                                <td><span class="badge text-bg-light border font-monospace">{{ $wh->code }}</span></td>
                                <td class="fw-semibold">{{ $wh->name }}</td>
                                <td>{{ $wh->kindLabel() }}</td>
                                <td class="text-center">{{ $wh->stock_lots_count }}</td>
                                <td>
                                    @if($wh->is_active)
                                        <span class="badge text-bg-success">Active</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.warehouses.show', $wh) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @can('warehouse.edit')
                                        <a href="{{ route('inventory.warehouses.edit', $wh) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-body-secondary py-4">No godowns yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
