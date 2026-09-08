<x-app-layout>
    <x-slot name="header">Costing {{ $costing->costing_num }}</x-slot>

    <x-ui.card :title="$costing->costing_num" variant="primary">
        <x-slot name="actions">
            @can('style-costing.approve')
                @if($costing->isDraft())
                    <form action="{{ route('style-costings.approve', $costing) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            <i class="bi bi-pen me-1"></i> Approve / Sign
                        </button>
                    </form>
                @endif
            @endcan
            @can('style-costing.edit')
                @if($costing->isDraft())
                    <a href="{{ route('style-costings.edit', $costing) }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </a>
                @endif
            @endcan
            @can('style-costing.create')
                @if($costing->isApproved() && $costing->garment_style_id)
                    <a href="{{ route('style-costings.create', ['style_id' => $costing->garment_style_id]) }}" class="btn btn-sm btn-outline-primary d-print-none">
                        New sheet
                    </a>
                @endif
            @endcan
            @can('style-costing.delete')
                @if($costing->isDraft())
                    <x-ui.delete-form :action="route('style-costings.destroy', $costing)"
                                      confirm="Delete this draft costing?" />
                @endif
            @endcan
            <button type="button" class="btn btn-sm btn-outline-secondary d-print-none" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print
            </button>
            <a href="{{ route('style-costings.index') }}" class="btn btn-sm btn-outline-secondary d-print-none">Back</a>
        </x-slot>

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Status</dt>
            <dd class="col-sm-9">
                <span class="badge text-bg-{{ $costing->statusColor() }}">{{ $costing->statusLabel() }}</span>
                @if($costing->approved_at)
                    <span class="text-body-secondary small ms-2">
                        Signed {{ $costing->approved_at->format('d M Y H:i') }}
                        @if($costing->approvedByUser) by {{ $costing->approvedByUser->name }} @endif
                    </span>
                @endif
            </dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Style</dt>
            <dd class="col-sm-9">
                {{ $costing->garmentStyle?->style_number }} — {{ $costing->garmentStyle?->name }}
                @if($costing->garmentStyle)
                    <a href="{{ route('masters.styles.show', $costing->garmentStyle) }}" class="small ms-1 d-print-none">Tech pack</a>
                @endif
            </dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Buyer</dt>
            <dd class="col-sm-9">{{ $costing->buyer?->company_name ?? '—' }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Date</dt>
            <dd class="col-sm-9">{{ $costing->costing_date?->format('d M Y') }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Notes</dt>
            <dd class="col-sm-9">{{ $costing->notes ?: '—' }}</dd>
        </dl>

        <h6 class="fw-semibold mb-2">This style costs</h6>
        <p class="display-6 fw-bold text-primary mb-4">₹ {{ number_format((float) $costing->total_cost_per_pc, 2) }} <span class="fs-6 fw-normal text-body-secondary">per piece</span></p>

        <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material</th>
                        <th>Type</th>
                        <th class="text-end">Qty / pc</th>
                        <th>Unit</th>
                        <th class="text-end">Rate ₹</th>
                        <th class="text-end">Amount ₹</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($costing->lines as $line)
                        <tr>
                            <td>{{ $line->description }}</td>
                            <td>{{ $line->kindLabel() }}</td>
                            <td class="text-end">{{ number_format((float) $line->qty_per_pc, 4) }}</td>
                            <td>{{ $line->unit ?: '—' }}</td>
                            <td class="text-end">{{ number_format((float) $line->rate, 4) }}</td>
                            <td class="text-end">{{ number_format((float) $line->amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-body-secondary text-center">No BOM lines — CM / other only.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">Material</th>
                        <th class="text-end">{{ number_format((float) $costing->material_cost, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Cut-make / CM</th>
                        <th class="text-end">{{ number_format((float) $costing->cm_cost, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Other</th>
                        <th class="text-end">{{ number_format((float) $costing->other_cost, 2) }}</th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Total / pc</th>
                        <th class="text-end">{{ number_format((float) $costing->total_cost_per_pc, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>
</x-app-layout>
