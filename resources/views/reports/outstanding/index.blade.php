<x-app-layout>
    <x-slot name="header">Outstanding</x-slot>

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <x-ui.card title="Supplier Outstanding (Payables)">
                <div class="fs-4 fw-semibold">{{ number_format($supplierOutstanding, 2) }}</div>
                <div class="small text-body-secondary">From Purchase Orders</div>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card title="Buyer Outstanding (Receivables)">
                <div class="fs-4 fw-semibold">{{ number_format($buyerOutstanding, 2) }}</div>
                <div class="small text-body-secondary">From Export Documents</div>
            </x-ui.card>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <x-ui.card title="Supplier Side">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>PO No.</th>
                                <th>Supplier</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchaseOrders as $po)
                                <tr>
                                    <td>{{ $po->po_num }}</td>
                                    <td>{{ $po->supplier?->company_name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($po->totalAmount(), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
        <div class="col-lg-6">
            <x-ui.card title="Buyer Side">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Export Doc</th>
                                <th>Buyer</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($exportDocuments as $doc)
                                <tr>
                                    <td>{{ $doc->doc_num }}</td>
                                    <td>{{ $doc->buyer?->company_name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($doc->totalAmount(), 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-body-secondary">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>

