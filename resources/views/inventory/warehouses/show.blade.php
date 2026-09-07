<x-app-layout>
    <x-slot name="header">{{ $warehouse->name }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <span class="badge text-bg-light border font-monospace me-2">{{ $warehouse->code }}</span>
            <span class="text-body-secondary small">{{ $warehouse->kindLabel() }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.lots', ['warehouse_id' => $warehouse->id]) }}" class="btn btn-sm btn-outline-secondary">Lots here</a>
            @can('warehouse.edit')
                <a href="{{ route('inventory.warehouses.edit', $warehouse) }}" class="btn btn-sm btn-outline-primary">Edit</a>
            @endcan
            <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </div>
    </div>

    @if($warehouse->remarks)
        <p class="small text-body-secondary">{{ $warehouse->remarks }}</p>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Lots in this godown</h6>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Lot / roll</th>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th>Received</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($warehouse->stockLots as $lot)
                            <tr>
                                <td class="font-monospace">{{ $lot->lot_no }}</td>
                                <td>{{ $lot->product?->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $lot->qty_on_hand, 3) }}</td>
                                <td class="small text-body-secondary">{{ $lot->received_at?->format('d M Y') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-body-secondary py-3">Empty godown.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
