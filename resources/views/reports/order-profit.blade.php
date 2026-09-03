<x-app-layout>
    <x-slot name="header">Profit per order</x-slot>

    <x-ui.card title="Plan vs actual" variant="primary">
        <p class="text-body-secondary small mb-3">
            Plan = approved style costing × order qty.
            Actual = stock used × costing rate + job-work receive charges + cut-make/other × cutting qty.
            Bought fabric on PO is not included here.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Production</th>
                        <th>Style</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Plan cost</th>
                        <th class="text-end">Actual</th>
                        <th class="text-end">Variance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('manufacturing.show', $row['order']) }}">{{ $row['order']->order_number }}</a>
                            </td>
                            <td>{{ $row['order']->garmentStyle?->style_number ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row['qty']) }}</td>
                            <td class="text-end">
                                @if($row['has_costing'])
                                    {{ number_format($row['plan_cost'], 2) }}
                                @else
                                    <span class="text-body-secondary">No costing</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($row['actual_cost'], 2) }}</td>
                            <td class="text-end {{ $row['variance'] < 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($row['variance'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary">No production orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>
