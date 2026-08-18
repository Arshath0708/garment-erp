<x-app-layout>
    <x-slot name="header">Debit Notes</x-slot>

    <x-ui.card title="Debit Notes Workbench">
        <p class="text-body-secondary small mb-3">
            Demo-ready debit-note workbench. It lists purchase orders where adjustments may be needed.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th class="text-end">PO Amount</th>
                        <th>Suggested Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                        @php $isLikely = in_array($po->status, ['partial', 'received'], true); @endphp
                        <tr>
                            <td>{{ $po->po_num }}</td>
                            <td>{{ $po->supplier?->company_name ?? '—' }}</td>
                            <td><span class="badge text-bg-{{ $po->statusColor() }}">{{ $po->statusLabel() }}</span></td>
                            <td class="text-end">{{ number_format($po->totalAmount(), 2) }}</td>
                            <td>{{ $isLikely ? 'Check qty/rate difference against inward' : 'No adjustment suggested yet' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary">No purchase orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $purchaseOrders->links() }}</div>
    </x-ui.card>
</x-app-layout>

