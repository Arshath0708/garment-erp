<x-app-layout>
    <x-slot name="header">Fabric &amp; accessory stock</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-body-secondary small mb-0">On-hand qty for items on style BOM. New similar orders can use this stock first, or you can buy new.</p>
        <a href="{{ route('masters.products.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add item</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-5">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Name or item code">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Type</label>
                    <select name="kind" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($kinds as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['kind'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th class="text-end">Qty on hand</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                    <div class="small text-body-secondary font-monospace">{{ $item->item_group_code }}</div>
                                </td>
                                <td>{{ $kinds[$item->item_kind] ?? $item->item_kind }}</td>
                                <td>{{ $item->unit_po ?: '—' }}</td>
                                <td class="text-end fw-bold {{ (float) $item->qty_on_hand <= 0 ? 'text-danger' : '' }}">{{ number_format((float) $item->qty_on_hand, 3) }}</td>
                                <td class="text-end"><a href="{{ route('masters.products.edit', $item) }}" class="btn btn-sm btn-outline-primary">Adjust</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No stock items yet. Add fabric/accessories in Item Master.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        </div>
    </div>
</x-app-layout>
