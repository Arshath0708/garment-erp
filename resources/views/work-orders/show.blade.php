<x-app-layout>
    <x-slot name="header">Work Order {{ $workOrder->wo_num }}</x-slot>

    <x-ui.card :title="$workOrder->wo_num" variant="primary">
        <x-slot name="actions">
            @can('work-order.approve')
                @if($workOrder->status !== 'released')
                    <form action="{{ route('work-orders.release', $workOrder) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-unlock me-1"></i> Release
                        </button>
                    </form>
                @endif
            @endcan
            @can('work-order.edit')
                @if($workOrder->status === 'released')
                    <form action="{{ route('work-orders.hold', $workOrder) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-pause-circle me-1"></i> Hold
                        </button>
                    </form>
                @endif
                <a href="{{ route('work-orders.edit', $workOrder) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @if($workOrder->isReleased())
                <a href="{{ route('manufacturing.create', ['work_order_id' => $workOrder->id]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-play-circle me-1"></i> Launch production
                </a>
            @endif
            <a href="{{ route('work-orders.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </x-slot>

        @if($workOrder->status !== 'released' && $workOrder->garmentStyle && ! $workOrder->garmentStyle->latestApprovedCosting())
            <div class="alert alert-warning">
                Approve a style costing for {{ $workOrder->garmentStyle->style_number }} before releasing this work order.
                <a href="{{ route('style-costings.create', ['style_id' => $workOrder->garment_style_id]) }}">Open costing</a>
            </div>
        @endif

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9">
                <span class="badge text-bg-{{ $workOrder->statusColor() }}">{{ $workOrder->statusLabel() }}</span>
                @if($workOrder->released_at)
                    <span class="text-body-secondary small ms-2">
                        Released {{ $workOrder->released_at->format('d M Y H:i') }}
                        @if($workOrder->releasedByUser) by {{ $workOrder->releasedByUser->name }} @endif
                    </span>
                @endif
            </dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Style</dt>
            <dd class="col-sm-9">{{ $workOrder->garmentStyle?->style_number }} — {{ $workOrder->garmentStyle?->name }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Buyer</dt>
            <dd class="col-sm-9">{{ $workOrder->buyer?->company_name ?? '—' }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Sales Order / OC</dt>
            <dd class="col-sm-9">{{ $workOrder->orderConfirmation?->oc_num ?? '—' }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Qty</dt>
            <dd class="col-sm-9">{{ number_format($workOrder->total_qty) }} pcs</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Target date</dt>
            <dd class="col-sm-9">{{ $workOrder->target_date?->format('d M Y') }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Notes</dt>
            <dd class="col-sm-9">{{ $workOrder->notes ?: '—' }}</dd>
        </dl>

        <h6 class="fw-semibold mb-2">Time &amp; Action</h6>
        <p class="text-body-secondary small">Planned dates count back from the target date. Red = late (no actual date, or actual after plan). Floor qty and fabric inward fill actual dates.</p>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Step</th>
                        <th>Planned</th>
                        <th>Actual</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($workOrder->steps as $step)
                        <tr class="{{ $step->isLate() && ! $step->actual_date ? 'table-danger' : '' }}">
                            <td>{{ $step->label }}</td>
                            <td>{{ $step->planned_date?->format('d M Y') }}</td>
                            <td>{{ $step->actual_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $step->statusColor() }}">{{ $step->statusLabel() }}</span>
                                @if($step->daysLate() > 0)
                                    <span class="small text-danger ms-1">{{ $step->daysLate() }}d</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($workOrder->productionOrders->isNotEmpty())
            <h6 class="fw-semibold mb-2">Production orders</h6>
            <ul class="mb-0">
                @foreach($workOrder->productionOrders as $order)
                    <li>
                        <a href="{{ route('manufacturing.edit', $order) }}">{{ $order->order_number }}</a>
                        — {{ $order->current_stage }} ({{ number_format($order->total_qty) }} pcs)
                    </li>
                @endforeach
            </ul>
        @endif
    </x-ui.card>
</x-app-layout>
