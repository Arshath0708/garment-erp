<x-app-layout>
    <x-slot name="header">Debit Notes</x-slot>

    <x-ui.card title="Debit Notes" variant="primary">
        <p class="text-body-secondary small mb-3">
            Notes raised from job-work damage (qty × rate on the receive voucher). Purchase orders below are a reminder to check inward shortages separately.
        </p>

        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Note no.</th>
                        <th>Date</th>
                        <th>Supplier / Jobber</th>
                        <th>Reason</th>
                        <th>Source</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $note)
                        <tr>
                            <td class="font-monospace">{{ $note->debit_note_num }}</td>
                            <td>{{ $note->note_date?->format('d M Y') }}</td>
                            <td>{{ $note->supplier?->company_name ?? '—' }}</td>
                            <td>{{ $note->reasonLabel() }}</td>
                            <td>
                                @if($note->source_type === \App\Models\DebitNote::SOURCE_JOB_WORK && $note->jobWorkVoucher)
                                    <a href="{{ route('job-work.show', $note->jobWorkVoucher) }}">{{ $note->jobWorkVoucher->voucher_num }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">{{ $note->qty ?: '—' }}</td>
                            <td class="text-end">{{ number_format((float) $note->amount, 2) }}</td>
                            <td>{{ \App\Models\DebitNote::STATUSES[$note->status] ?? $note->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-body-secondary">No debit notes yet. Raise one from a job-work receive with damage.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $notes->links() }}</div>
    </x-ui.card>

    <x-ui.card title="Purchase orders to review" class="mt-4">
        <p class="text-body-secondary small mb-3">Partial or received POs may need a qty/rate adjustment against inward.</p>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO No.</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th class="text-end">PO Amount</th>
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
    </x-ui.card>
</x-app-layout>
