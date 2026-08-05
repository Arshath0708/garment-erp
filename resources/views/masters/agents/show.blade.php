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

            <dt class="col-sm-3 text-body-secondary fw-normal">Categories Handled</dt>
            <dd class="col-sm-9">
                @forelse($agent->categories as $category)
                    <span class="badge text-bg-info me-1">{{ $category->name }}</span>
                @empty
                    <span class="text-body-secondary small">No categories linked.</span>
                @endforelse
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9"><x-ui.status-badge :status="$agent->status === 'active'" /></dd>

            <dt class="col-12"><hr class="my-3"></dt>

            <dt class="col-sm-3 text-body-secondary fw-normal">Phone</dt>
            <dd class="col-sm-9">{{ $agent->phone ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">City</dt>
            <dd class="col-sm-9">{{ $agent->city ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Address</dt>
            <dd class="col-sm-9">{{ $agent->address ?: '—' }}</dd>

            {{-- Tax details are collected for domestic sides only, so the rows
                 are hidden rather than shown empty for a buyer-side agent. --}}
            @if($agent->collectsTaxDetails())
                <dt class="col-12"><hr class="my-3"></dt>

                <dt class="col-sm-3 text-body-secondary fw-normal">GST Number</dt>
                <dd class="col-sm-9 font-monospace">{{ $agent->gst_number ?: '—' }}</dd>

                <dt class="col-sm-3 text-body-secondary fw-normal">PAN Number</dt>
                <dd class="col-sm-9 font-monospace">{{ $agent->pan_number ?: '—' }}</dd>
            @endif

            <dt class="col-12"><hr class="my-3"></dt>

            <dt class="col-sm-3 text-body-secondary fw-normal">Commission Basis</dt>
            <dd class="col-sm-9">{{ $agent->commissionBasis?->name ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Commission Entries</dt>
            <dd class="col-sm-9">
                @forelse($agent->commissions as $commission)
                    <span class="badge text-bg-light border font-monospace me-1">{{ $commission->label }}</span>
                @empty
                    <span class="text-body-secondary small">No commission entries.</span>
                @endforelse
            </dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Who Pays</dt>
            <dd class="col-sm-9">{{ \App\Models\Agent::COMMISSION_PAYERS[$agent->commission_paid_by] ?? '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Payment Terms</dt>
            <dd class="col-sm-9">
                {{ \App\Models\Agent::PAYMENT_TERMS[$agent->payment_term] ?? '—' }}
                @if($agent->payment_term === 'custom' && $agent->payment_term_custom)
                    <div class="small text-body-secondary">{{ $agent->payment_term_custom }}</div>
                @endif
            </dd>

            <dt class="col-12"><hr class="my-3"></dt>

            <dt class="col-sm-3 text-body-secondary fw-normal">Bank Name</dt>
            <dd class="col-sm-9">{{ $agent->bank_name ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Account Number</dt>
            <dd class="col-sm-9 font-monospace">{{ $agent->account_number ?: '—' }}</dd>

            {{-- One rail or the other: IFSC domestically, SWIFT abroad. --}}
            @if($agent->bankCodeField() === 'ifsc_code')
                <dt class="col-sm-3 text-body-secondary fw-normal">IFSC Code</dt>
                <dd class="col-sm-9 font-monospace">{{ $agent->ifsc_code ?: '—' }}</dd>
            @else
                <dt class="col-sm-3 text-body-secondary fw-normal">SWIFT Code</dt>
                <dd class="col-sm-9 font-monospace">{{ $agent->swift_code ?: '—' }}</dd>
            @endif

            <dt class="col-12"><hr class="my-3"></dt>

            <dt class="col-sm-3 text-body-secondary fw-normal">Remarks</dt>
            <dd class="col-sm-9">{{ $agent->remarks ?: '—' }}</dd>

            <dt class="col-sm-3 text-body-secondary fw-normal">Comments</dt>
            <dd class="col-sm-9">{{ $agent->comments ?: '—' }}</dd>

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
