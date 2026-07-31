<x-app-layout>
    <x-slot name="header">Agent Details</x-slot>

    <x-ui.card title="Agent Details: {{ $agent->display_code }}" variant="primary">
        <x-slot name="actions">
            @can('agent.edit')
                <a href="{{ route('masters.agents.edit', $agent) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.agents.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <dl class="row mb-0">
            <dt class="col-sm-3 text-body-secondary fw-normal">Display Code</dt>
            <dd class="col-sm-9"><span class="badge text-bg-light border font-monospace">{{ $agent->display_code }}</span></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Agent Name</dt>
            <dd class="col-sm-9 fw-semibold">{{ $agent->name }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Agent Type</dt>
            <dd class="col-sm-9"><span class="badge text-bg-secondary text-uppercase">{{ $agent->agent_type }}</span></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Commission Basis</dt>
            <dd class="col-sm-9">{{ $agent->commissionBasis?->name ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Commission Rate (Optional)</dt>
            <dd class="col-sm-9">{{ $agent->commission_rate ? number_format($agent->commission_rate, 2) : '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9"><x-ui.status-badge :status="$agent->status === 'active'" /></dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Categories Handled</dt>
            <dd class="col-sm-9">
                @forelse($agent->categories as $category)
                    <span class="badge text-bg-info me-1">{{ $category->name }}</span>
                @empty
                    <span class="text-body-secondary small">No categories linked.</span>
                @endforelse
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Remarks</dt>
            <dd class="col-sm-9">{{ $agent->remarks ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Created</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $agent->created_at?->format('d M Y, H:i') }}
                @if($agent->creator) by {{ $agent->creator->name }} @endif
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Last updated</dt>
            <dd class="col-sm-9 text-body-secondary small">
                {{ $agent->updated_at?->format('d M Y, H:i') }}
                @if($agent->updater) by {{ $agent->updater->name }} @endif
            </dd>
        </dl>
    </x-ui.card>
</x-app-layout>
