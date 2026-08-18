<x-app-layout>
    <x-slot name="header">Supplier Payments</x-slot>

    <x-ui.card title="Supplier Payments Tracker">
        <p class="text-body-secondary small mb-3">
            Payables view from raised purchase orders. Use this for supplier payment planning during demo.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th class="text-end">Payable Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td>{{ $po->po_num }}</td>
                            <td>{{ $po->supplier?->company_name ?? '—' }}</td>
                            <td><span class="badge text-bg-{{ $po->statusColor() }}">{{ $po->statusLabel() }}</span></td>
                            <td class="text-end">{{ number_format($po->totalAmount(), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-body-secondary">No purchase orders found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $purchaseOrders->links() }}</div>
    </x-ui.card>
</x-app-layout>

