<x-app-layout>
    <x-slot name="header">Buyer Receipts</x-slot>

    <x-ui.card title="Buyer Receipts Tracker">
        <p class="text-body-secondary small mb-3">
            Receivables view from Export Documents, with payment checklist progress.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Export Doc</th>
                        <th>Buyer</th>
                        <th class="text-end">Invoice Value</th>
                        <th>Payment Proof</th>
                        <th>eBRC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        @php
                            $payment = $doc->checklist->first(fn ($row) => $row->type?->code === 'payment_received');
                            $ebrc = $doc->checklist->first(fn ($row) => $row->type?->code === 'ebrc');
                        @endphp
                        <tr>
                            <td>{{ $doc->doc_num }}</td>
                            <td>{{ $doc->buyer?->company_name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($doc->totalAmount(), 2) }}</td>
                            <td>
                                @if($payment)
                                    <span class="badge text-bg-{{ $payment->statusColor() }}">{{ $payment->statusLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($ebrc)
                                    <span class="badge text-bg-{{ $ebrc->statusColor() }}">{{ $ebrc->statusLabel() }}</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-body-secondary">No export documents found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $documents->links() }}</div>
    </x-ui.card>
</x-app-layout>

