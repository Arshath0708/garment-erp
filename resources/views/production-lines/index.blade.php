<x-app-layout>
    <x-slot name="header">Line efficiency</x-slot>

    <x-ui.card title="Sewing line output" variant="primary">
        <p class="text-body-secondary small mb-3">Log today’s pcs against each line’s target. Efficiency = pcs ÷ target.</p>

        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Line</th>
                        <th class="text-end">Target / day</th>
                        <th class="text-end">Today (pcs)</th>
                        <th class="text-end">Efficiency</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                        @php $pct = $line->efficiencyPct(); @endphp
                        <tr>
                            <td class="fw-semibold">{{ $line->name }}</td>
                            <td class="text-end">{{ number_format($line->target_pcs_per_day) }}</td>
                            <td class="text-end">{{ number_format($line->todaysPcs()) }}</td>
                            <td class="text-end">
                                <span class="badge text-bg-{{ $pct >= 100 ? 'success' : ($pct >= 70 ? 'warning' : 'secondary') }}">{{ number_format($pct, 1) }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @can('work-order.edit')
            <h6 class="fw-semibold mb-2">Log output</h6>
            <form action="{{ route('production-lines.outputs.store') }}" method="POST" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-md-3">
                    <label class="form-label small">Line</label>
                    <select name="production_line_id" class="form-select form-select-sm" required>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}">{{ $line->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date</label>
                    <input type="date" name="output_date" class="form-control form-control-sm" value="{{ old('output_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Pcs</label>
                    <input type="number" name="pcs" min="0" class="form-control form-control-sm" value="{{ old('pcs') }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Production order</label>
                    <select name="production_order_id" class="form-select form-select-sm">
                        <option value="">— Optional —</option>
                        @foreach($orders as $order)
                            <option value="{{ $order->id }}">{{ $order->order_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Save</button>
                </div>
                <div class="col-12">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)" value="{{ old('notes') }}">
                </div>
            </form>
        @endcan

        <h6 class="fw-semibold mb-2">Recent logs</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Line</th>
                        <th>Order</th>
                        <th class="text-end">Pcs</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $row)
                        <tr>
                            <td>{{ $row->output_date?->format('d M Y') }}</td>
                            <td>{{ $row->line?->name }}</td>
                            <td>{{ $row->productionOrder?->order_number ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row->pcs) }}</td>
                            <td>{{ $row->creator?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary">No output logged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>
