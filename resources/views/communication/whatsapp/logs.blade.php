<x-app-layout>
    <x-slot name="header">WhatsApp log</x-slot>

    <x-ui.card title="Sent and opened messages" variant="primary">
        <x-slot name="actions">
            <a href="{{ route('whatsapp.settings') }}" class="btn btn-sm btn-outline-secondary">Settings</a>
        </x-slot>

        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>When</th>
                        <th>To</th>
                        <th>Source</th>
                        <th>Channel</th>
                        <th>Status</th>
                        <th>By</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-nowrap small">{{ $log->sent_at?->format('d M Y, H:i') }}</td>
                            <td class="font-monospace">{{ $log->to_digits }}</td>
                            <td class="small">{{ $log->source_type }} #{{ $log->source_id }}</td>
                            <td>{{ $log->channel === 'api' ? 'Cloud API' : 'Open WhatsApp' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $log->status === 'failed' ? 'danger' : ($log->status === 'sent' ? 'success' : 'secondary') }}">
                                    {{ $log->statusLabel() }}
                                </span>
                                @if($log->error_message)
                                    <div class="small text-danger mt-1">{{ $log->error_message }}</div>
                                @endif
                            </td>
                            <td>{{ $log->sender?->name ?? '—' }}</td>
                            <td class="small" style="max-width:22rem">{{ \Illuminate\Support\Str::limit($log->body, 120) }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-state :colspan="7" icon="bi-whatsapp" title="No WhatsApp messages yet"
                                          message="Open WhatsApp from a purchase order or a late Time &amp; Action row." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="mt-3">{{ $logs->links() }}</div>
        @endif
    </x-ui.card>
</x-app-layout>
