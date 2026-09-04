<x-app-layout>
    <x-slot name="header">Lots / rolls</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-body-secondary small mb-0">Stock by godown and lot/roll number. New inward creates a lot when stores receive.</p>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary">Item stock</a>
            @can('warehouse.view')
                <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-sm btn-outline-primary">Godowns</a>
            @endcan
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Lot no or item">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Godown</label>
                    <select name="warehouse_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach ($warehouses as $wh)
                            <option value="{{ $wh->id }}" @selected((string) ($filters['warehouse_id'] ?? '') === (string) $wh->id)>{{ $wh->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-secondary">Filter</button>
                    <a href="{{ route('inventory.lots') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lot / roll</th>
                            <th>Item</th>
                            <th>Godown</th>
                            <th class="text-end">Qty</th>
                            <th>Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lots as $lot)
                            <tr>
                                <td class="font-monospace fw-semibold">{{ $lot->lot_no }}</td>
                                <td>
                                    <span class="fw-semibold">{{ $lot->product?->name ?? '—' }}</span>
                                    <div class="small text-body-secondary font-monospace">{{ $lot->product?->item_group_code }}</div>
                                </td>
                                <td>{{ $lot->warehouse?->name ?? '—' }} <span class="small text-body-secondary">({{ $lot->warehouse?->code }})</span></td>
                                <td class="text-end fw-bold">{{ number_format((float) $lot->qty_on_hand, 3) }} {{ $lot->product?->unit_po }}</td>
                                <td class="small text-body-secondary">{{ $lot->received_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-body-secondary py-4">No lots yet. Receive a QC-passed inward into a godown.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $lots->links() }}
        </div>
    </div>
</x-app-layout>
