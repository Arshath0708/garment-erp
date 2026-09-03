<x-app-layout>
    <x-slot name="header">Work Orders</x-slot>

    <x-ui.card title="Work Order" variant="primary">
        <x-slot name="actions">
            @can('work-order.create')
                <a href="{{ route('work-orders.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> New Work Order
                </a>
            @endcan
            @can('work-order.view')
                <a href="{{ route('time-and-action.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-calendar-week me-1"></i> Time &amp; Action
                </a>
            @endcan
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Merchandiser releases a work order first. Production Planning cannot start until status is <strong>Released</strong>.
            Time &amp; Action dates are counted back from the target date.
        </p>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small text-body-secondary mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="WO no., style, buyer">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\WorkOrder::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-secondary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('work-orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>WO No.</th>
                        <th>Style</th>
                        <th>Buyer</th>
                        <th>OC</th>
                        <th class="text-end">Qty</th>
                        <th>Target</th>
                        <th>Status</th>
                        <th>T&amp;A late</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workOrders as $workOrder)
                        <tr>
                            <td class="font-monospace">{{ $workOrder->wo_num }}</td>
                            <td>{{ $workOrder->garmentStyle?->style_number }} — {{ $workOrder->garmentStyle?->name }}</td>
                            <td>{{ $workOrder->buyer?->company_name ?? '—' }}</td>
                            <td class="font-monospace small">{{ $workOrder->orderConfirmation?->oc_num ?? '—' }}</td>
                            <td class="text-end">{{ number_format($workOrder->total_qty) }}</td>
                            <td>{{ $workOrder->target_date?->format('d M Y') }}</td>
                            <td>
                                <span class="badge text-bg-{{ $workOrder->statusColor() }}">{{ $workOrder->statusLabel() }}</span>
                            </td>
                            <td>
                                @php $late = $workOrder->lateStepsCount(); @endphp
                                @if($late > 0)
                                    <span class="badge text-bg-danger">{{ $late }} late</span>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('work-orders.show', $workOrder) }}" class="btn btn-sm btn-outline-secondary" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @can('work-order.edit')
                                    <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state colspan="9"
                                          icon="bi-clipboard-check"
                                          title="No work orders yet"
                                          message="Add a work order and release it before launching production." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($workOrders->hasPages())
            <div class="mt-3">{{ $workOrders->links() }}</div>
        @endif
    </x-ui.card>
</x-app-layout>
