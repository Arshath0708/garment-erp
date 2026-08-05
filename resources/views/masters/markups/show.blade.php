<x-app-layout>
    <x-slot name="header">Markup Rule</x-slot>

    <x-ui.card title="{{ $markup->supplier?->company_name }} &rarr; {{ $markup->buyer?->company_name }}" variant="primary">
        <x-slot name="actions">
            @can('markup.edit')
                <a href="{{ route('masters.markups.edit', $markup) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.markups.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <div class="row g-4">
            <div class="col-lg-7">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Supplier</dt>
                    <dd class="col-sm-7">
                        <span class="badge text-bg-light border font-monospace me-1">{{ $markup->supplier?->display_code }}</span>
                        {{ $markup->supplier?->company_name ?: '—' }}
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Buyer</dt>
                    <dd class="col-sm-7">
                        <span class="badge text-bg-light border font-monospace me-1">{{ $markup->buyer?->display_code }}</span>
                        {{ $markup->buyer?->company_name ?: '—' }}
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Record Date</dt>
                    <dd class="col-sm-7">{{ $markup->record_date?->format('d M Y') ?: '—' }}</dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Supplier's Agent Commission</dt>
                    <dd class="col-sm-7">
                        @if($markup->supplierAgent())
                            {{ $markup->supplierAgent()->label }}
                            @if($markup->supplierAgentCommissionLabel())
                                — {{ $markup->supplierAgentCommissionLabel() }}
                            @endif
                        @else
                            No agent linked
                        @endif
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Buyer's Agent Commission</dt>
                    <dd class="col-sm-7">
                        @if($markup->buyerAgent())
                            {{ $markup->buyerAgent()->label }}
                            @if($markup->buyerAgentCommissionLabel())
                                — {{ $markup->buyerAgentCommissionLabel() }}
                            @endif
                        @else
                            No agent linked
                        @endif
                    </dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Markup %</dt>
                    <dd class="col-sm-7 fw-semibold">
                        {{ rtrim(rtrim(number_format((float) $markup->markup_percent, 2), '0'), '.') }}%
                        <div class="small text-body-secondary fw-normal">Added on top of cost price &rarr; client price.</div>
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Discount %</dt>
                    <dd class="col-sm-7">
                        {{ rtrim(rtrim(number_format($preview['discount'], 2), '0'), '.') }}%
                        <div class="small text-body-secondary">
                            From the Supplier Master &middot;
                            @can('supplier.view')
                                <a href="{{ route('masters.suppliers.show', $markup->supplier) }}">edit there to change</a>.
                            @else
                                edit there to change.
                            @endcan
                        </div>
                    </dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Status</dt>
                    <dd class="col-sm-7"><x-ui.status-badge :status="$markup->status === 'active'" /></dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Remarks</dt>
                    <dd class="col-sm-7">{{ $markup->remarks ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Created</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $markup->created_at?->format('d M Y, H:i') }}
                        @if($markup->creator) by {{ $markup->creator->name }} @endif
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Last updated</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $markup->updated_at?->format('d M Y, H:i') }}
                        @if($markup->updater) by {{ $markup->updater->name }} @endif
                    </dd>
                </dl>
            </div>

            {{-- What the rule actually does, worked against ₹100. A percentage
                 on its own is not something anyone can sanity-check. --}}
            <div class="col-lg-5">
                <div class="card border h-100">
                    <div class="card-header bg-body-tertiary py-2">
                        <span class="small fw-semibold text-uppercase text-body-secondary" style="letter-spacing:.06em">
                            Worked example
                        </span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-7 fw-normal text-body-secondary">Cost price (CP)</dt>
                            <dd class="col-5 text-end font-monospace">₹{{ number_format($preview['cost'], 2) }}</dd>

                            <dt class="col-7 fw-normal text-body-secondary">
                                CP + {{ rtrim(rtrim(number_format((float) $markup->markup_percent, 2), '0'), '.') }}%
                                = Client Price
                            </dt>
                            <dd class="col-5 text-end font-monospace fw-semibold">₹{{ number_format($preview['client_price'], 2) }}</dd>

                            <dt class="col-7 fw-normal text-body-secondary">
                                CP − {{ rtrim(rtrim(number_format($preview['discount'], 2), '0'), '.') }}%
                                = Our Cost
                            </dt>
                            <dd class="col-5 text-end font-monospace">₹{{ number_format($preview['our_cost'], 2) }}</dd>

                            <dd class="col-12"><hr class="my-2"></dd>

                            <dt class="col-7 fw-semibold">Profit</dt>
                            <dd class="col-5 text-end font-monospace fw-semibold text-success">
                                ₹{{ number_format($preview['profit'], 2) }}
                            </dd>
                        </dl>

                        <div class="small text-body-secondary mt-3">
                            Applied per cost price at OC / PO entry.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>
</x-app-layout>
