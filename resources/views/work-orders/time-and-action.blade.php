<x-app-layout>
    <x-slot name="header">Time &amp; Action</x-slot>

    <x-ui.card title="Time &amp; Action calendar" variant="primary">
        <x-slot name="actions">
            @if($lateOnly)
                <a href="{{ route('time-and-action.index') }}" class="btn btn-sm btn-outline-secondary">Show all released</a>
            @else
                <a href="{{ route('time-and-action.index', ['late' => 1]) }}" class="btn btn-sm btn-outline-danger">Late only</a>
            @endif
            <a href="{{ route('work-orders.index') }}" class="btn btn-sm btn-outline-secondary">Work Orders</a>
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Released work orders only. Late = planned date has passed and the step is not done, or it was done after the plan.
        </p>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>WO</th>
                        <th>Style</th>
                        <th>Buyer</th>
                        <th>Step</th>
                        <th>Planned</th>
                        <th>Actual</th>
                        <th>Status</th>
                        @can('whatsapp.send')
                            <th class="text-end">WhatsApp</th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @forelse($steps as $step)
                        <tr class="{{ $step->isLate() && ! $step->actual_date ? 'table-danger' : '' }}">
                            <td>
                                <a href="{{ route('work-orders.show', $step->workOrder) }}" class="font-monospace">
                                    {{ $step->workOrder->wo_num }}
                                </a>
                            </td>
                            <td>{{ $step->workOrder->garmentStyle?->style_number }}</td>
                            <td>{{ $step->workOrder->buyer?->company_name ?? '—' }}</td>
                            <td>{{ $step->label }}</td>
                            <td>{{ $step->planned_date?->format('d M Y') }}</td>
                            <td>{{ $step->actual_date?->format('d M Y') ?? '—' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $step->statusColor() }}">{{ $step->statusLabel() }}</span>
                                @if($step->daysLate() > 0)
                                    <span class="small text-danger ms-1">{{ $step->daysLate() }}d late</span>
                                @endif
                            </td>
                            @can('whatsapp.send')
                                <td class="text-end">
                                    @if($step->isLate())
                                        <x-whatsapp.notify :step="$step" compact />
                                    @else
                                        <span class="text-body-secondary">—</span>
                                    @endif
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <x-ui.empty-state colspan="{{ auth()->user()?->can('whatsapp.send') ? 8 : 7 }}"
                                          icon="bi-calendar-week"
                                          title="{{ $lateOnly ? 'Nothing late' : 'No T&A rows yet' }}"
                                          message="Release a work order to build the calendar from its target date." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>
