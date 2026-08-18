<x-app-layout>
    <x-slot name="header">Agent Commission</x-slot>

    <x-ui.card title="Agent Commission Overview">
        <p class="text-body-secondary small mb-3">
            Commission calculation preview from purchase orders using supplier-agent mapping and configured rates.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier</th>
                        <th>Agent</th>
                        <th class="text-end">PO Amount</th>
                        <th class="text-end">Commission</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $po)
                        <tr>
                            <td>{{ $po->po_num }}</td>
                            <td>{{ $po->supplier?->company_name ?? '—' }}</td>
                            <td>{{ $po->supplier?->agent?->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($po->totalAmount(), 2) }}</td>
                            <td class="text-end">{{ $po->agentCommissionAmountLabel() ?? '—' }}</td>
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

