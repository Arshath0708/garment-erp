<x-app-layout>
    <x-slot name="header">Factory board</x-slot>

    <x-ui.card title="Style → work order → floor qty" variant="primary">
        <x-slot name="actions">
            @if($lateOnly)
                <a href="{{ route('reports.factory-board') }}" class="btn btn-sm btn-outline-secondary">Show all</a>
            @else
                <a href="{{ route('reports.factory-board', ['late' => 1]) }}" class="btn btn-sm btn-outline-danger">Late only</a>
            @endif
            @can('report.export')
                <a href="{{ route('reports.factory-board.export', $lateOnly ? ['late' => 1] : []) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i> CSV for Power BI
                </a>
            @endcan
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Live qty from production orders — cutting, stitching, packing, dispatch — with the work order and style on the same row.
            This is the floor view they asked for in Power BI. Download the CSV and refresh it in Power BI; the ERP is the source, not a second copy.
        </p>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100">
                    <div class="small text-body-secondary">Orders</div>
                    <div class="fs-5 fw-semibold">{{ number_format($totals['orders']) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100">
                    <div class="small text-body-secondary">Cutting pcs</div>
                    <div class="fs-5 fw-semibold">{{ number_format($totals['cutting']) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100">
                    <div class="small text-body-secondary">Stitching pcs</div>
                    <div class="fs-5 fw-semibold">{{ number_format($totals['stitching']) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100">
                    <div class="small text-body-secondary">Packing pcs</div>
                    <div class="fs-5 fw-semibold">{{ number_format($totals['packing']) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100">
                    <div class="small text-body-secondary">Dispatch pcs</div>
                    <div class="fs-5 fw-semibold">{{ number_format($totals['dispatch']) }}</div>
                </div>
            </div>
            <div class="col-6 col-lg">
                <div class="border rounded p-3 h-100 {{ $totals['late'] ? 'border-danger' : '' }}">
                    <div class="small text-body-secondary">Late orders</div>
                    <div class="fs-5 fw-semibold {{ $totals['late'] ? 'text-danger' : '' }}">{{ number_format($totals['late']) }}</div>
                </div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 small mb-3">
            @can('work-order.view')
                <a href="{{ route('time-and-action.index', ['late' => 1]) }}">{{ $lateTna }} late Time &amp; Action step{{ $lateTna === 1 ? '' : 's' }}</a>
            @else
                <span class="text-body-secondary">{{ $lateTna }} late Time &amp; Action step{{ $lateTna === 1 ? '' : 's' }}</span>
            @endcan
            <a href="{{ route('inventory.index') }}">{{ $lowStock }} item{{ $lowStock === 1 ? '' : 's' }} at or below reorder</a>
            <a href="{{ route('reports.order-profit') }}">Profit per order</a>
            <a href="{{ route('reports.outstanding.index') }}">Outstanding</a>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Style</th>
                        <th>WO</th>
                        <th>Production</th>
                        <th>Buyer</th>
                        <th class="text-end">Target</th>
                        <th class="text-end">Cut</th>
                        <th class="text-end">Stitch</th>
                        <th class="text-end">Pack</th>
                        <th class="text-end">Dispatch</th>
                        <th>Target date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr class="{{ $row['is_late'] ? 'table-danger' : '' }}">
                            <td>
                                @if($row['order']->garment_style_id)
                                    <a href="{{ route('masters.styles.show', $row['order']->garment_style_id) }}">{{ $row['style'] ?: '—' }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($row['order']->work_order_id)
                                    <a href="{{ route('work-orders.show', $row['order']->work_order_id) }}" class="font-monospace">{{ $row['wo_num'] }}</a>
                                @else
                                    <span class="text-body-secondary">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('manufacturing.show', $row['order']) }}" class="font-monospace">{{ $row['order']->order_number }}</a>
                            </td>
                            <td>{{ $row['buyer'] ?: '—' }}</td>
                            <td class="text-end">{{ number_format($row['total_qty']) }}</td>
                            <td class="text-end">{{ number_format($row['cutting']) }}</td>
                            <td class="text-end">{{ number_format($row['stitching']) }}</td>
                            <td class="text-end">{{ number_format($row['packing']) }}</td>
                            <td class="text-end">{{ number_format($row['dispatch']) }}</td>
                            <td>{{ $row['target']?->format('d M Y') ?? '—' }}</td>
                            <td>
                                @if($row['is_late'])
                                    <span class="badge text-bg-danger">Late{{ $row['late_steps'] ? ' · '.$row['late_steps'].' T&A' : '' }}</span>
                                @else
                                    <span class="badge text-bg-secondary">{{ $row['status'] ?: '—' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state :colspan="11" icon="bi-bar-chart-line"
                                          title="{{ $lateOnly ? 'Nothing late' : 'No production orders yet' }}"
                                          message="Launch a production order from a released work order to fill this board." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>
