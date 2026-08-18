<x-app-layout>
    <x-slot name="header">Purchase Bills</x-slot>

    <x-ui.card title="Purchase Bills (E-Sanchit)">
        <p class="text-body-secondary small mb-3">
            Generated purchase-bills entries from Export Documents. Use this screen for finance-side tracking and quick file access.
        </p>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Export Doc</th>
                        <th>Buyer</th>
                        <th>Variant</th>
                        <th>Status</th>
                        <th>Generated At</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>{{ $row->exportDocument?->doc_num ?? '—' }}</td>
                            <td>{{ $row->exportDocument?->buyer?->company_name ?? '—' }}</td>
                            <td>{{ $row->variantLabel() ?? 'Standard' }}</td>
                            <td><span class="badge text-bg-{{ $row->statusColor() }}">{{ $row->statusLabel() }}</span></td>
                            <td>{{ $row->generated_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td>
                                @if($row->hasFile())
                                    <a href="{{ $row->fileUrl() }}" target="_blank" rel="noopener">View</a>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-body-secondary">No purchase-bill checklist entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $rows->links() }}</div>
    </x-ui.card>
</x-app-layout>

