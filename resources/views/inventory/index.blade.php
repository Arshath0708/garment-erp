<x-app-layout>
    <x-slot name="header">Fabric &amp; accessory stock</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-body-secondary small mb-0">On-hand qty for items on style BOM. Track by godown and lot/roll after stores receive. New similar orders can use this stock first, or you can buy new.</p>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.lots') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-upc-scan me-1"></i> Lots / rolls</a>
            @can('warehouse.view')
                <a href="{{ route('inventory.warehouses.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-building me-1"></i> Godowns ({{ $warehouseCount ?? 0 }})</a>
            @endcan
            <a href="{{ route('masters.products.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i> Add item</a>
        </div>
    </div>

    @if(($lowStock ?? collect())->isNotEmpty())
        <div class="alert alert-warning">
            <strong>{{ $lowStock->count() }} item{{ $lowStock->count() === 1 ? '' : 's' }} at or below reorder level.</strong>
            Raise a fabric/trims PO — stock is not ordered automatically.
        </div>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Low stock</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">On hand</th>
                                <th class="text-end">Reorder at</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lowStock as $item)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ $item->name }}</span>
                                        <div class="small text-body-secondary font-monospace">{{ $item->item_group_code }}</div>
                                    </td>
                                    <td class="text-end text-danger fw-bold">{{ number_format((float) $item->qty_on_hand, 3) }}</td>
                                    <td class="text-end">{{ number_format((float) $item->reorder_level, 3) }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('procurement.purchase-orders.create', ['product_id' => $item->id]) }}" class="btn btn-sm btn-outline-primary">Raise PO</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

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
                            <th class="text-end">Lots</th>
                            <th class="text-end">Reorder</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr class="{{ $item->isBelowReorder() ? 'table-warning' : '' }}">
                                <td>
                                    <span class="fw-semibold">{{ $item->name }}</span>
                                    <div class="small text-body-secondary font-monospace">{{ $item->item_group_code }}</div>
                                </td>
                                <td>{{ $kinds[$item->item_kind] ?? $item->item_kind }}</td>
                                <td>{{ $item->unit_po ?: '—' }}</td>
                                <td class="text-end fw-bold {{ $item->isBelowReorder() || (float) $item->qty_on_hand <= 0 ? 'text-danger' : '' }}">{{ number_format((float) $item->qty_on_hand, 3) }}</td>
                                <td class="text-end">
                                    @if(($item->stock_lots_count ?? 0) > 0)
                                        <a href="{{ route('inventory.lots', ['product_id' => $item->id]) }}">{{ $item->stock_lots_count }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">{{ (float) $item->reorder_level > 0 ? number_format((float) $item->reorder_level, 3) : '—' }}</td>
                                <td class="text-end">
                                    @if($item->isBelowReorder())
                                        <a href="{{ route('procurement.purchase-orders.create', ['product_id' => $item->id]) }}" class="btn btn-sm btn-outline-primary me-1">Raise PO</a>
                                    @endif
                                    <a href="{{ route('masters.products.edit', $item) }}" class="btn btn-sm btn-outline-secondary">Adjust</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-body-secondary py-4">No stock items yet. Add fabric/accessories in Item Master.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        </div>
    </div>

    <div class="card shadow-sm border-0 mt-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Finished garments</h6>
            <p class="small text-body-secondary mb-3">Packed carton qty posted from Export Document packing. Ready FG stock by style.</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Style</th>
                            <th>Name</th>
                            <th class="text-end">Qty on hand</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($finishedGoods as $row)
                            <tr>
                                <td class="font-monospace">
                                    @if($row->garmentStyle)
                                        <a href="{{ route('masters.styles.show', $row->garmentStyle) }}">{{ $row->garmentStyle->style_number }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $row->garmentStyle?->name ?? '—' }}</td>
                                <td class="text-end fw-bold">{{ number_format($row->qty_on_hand) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-body-secondary py-4">No packed finished-garment stock yet. Record cartons on the export document.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
