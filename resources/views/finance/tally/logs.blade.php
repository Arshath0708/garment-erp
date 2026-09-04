<x-app-layout>
    <x-slot name="header">Tally posting log</x-slot>

    <x-ui.card title="Tally posting log" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('finance.tally.settings') }}" class="btn btn-sm btn-outline-secondary">Settings</a>
        </x-slot>

        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>Voucher</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>By</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>{{ $log->posted_at?->format('d M Y H:i') }}</td>
                            <td class="font-monospace">{{ $log->voucher_number ?: '—' }}</td>
                            <td>{{ $log->voucher_type }}</td>
                            <td>{{ $log->statusLabel() }}</td>
                            <td>{{ $log->poster?->name ?? '—' }}</td>
                            <td class="small text-body-secondary">{{ $log->error_message ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-body-secondary">No XML downloads or posts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $logs->links() }}</div>
    </x-ui.card>
</x-app-layout>
