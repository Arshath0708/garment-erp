<x-app-layout>
    <x-slot name="header">{{ $voucher->voucher_num }}</x-slot>

    <x-ui.card :title="$voucher->voucher_num" variant="primary">
        <x-slot name="actions">
            @can('job-work.delete')
                <x-ui.delete-form :action="route('job-work.destroy', $voucher)"
                                  confirm="Delete this voucher?" />
            @endcan
            @can('job-work.create')
                @if($voucher->production_order_id)
                    <a href="{{ route('job-work.create', ['type' => 'receive', 'production_order_id' => $voucher->production_order_id]) }}"
                       class="btn btn-sm btn-success">Receive against this order</a>
                @endif
            @endcan
            <a href="{{ route('job-work.index') }}" class="btn btn-sm btn-outline-secondary">Back</a>
        </x-slot>

        <dl class="row mb-4">
            <dt class="col-sm-3 text-body-secondary fw-normal">Type</dt>
            <dd class="col-sm-9"><span class="badge text-bg-{{ $voucher->typeColor() }}">{{ $voucher->typeLabel() }}</span></dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Date</dt>
            <dd class="col-sm-9">{{ $voucher->voucher_date?->format('d M Y') }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Jobber</dt>
            <dd class="col-sm-9">{{ $voucher->jobber?->company_name ?? '—' }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Production</dt>
            <dd class="col-sm-9">
                @if($voucher->productionOrder)
                    <a href="{{ route('manufacturing.show', $voucher->productionOrder) }}">{{ $voucher->productionOrder->order_number }}</a>
                @else
                    —
                @endif
            </dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Style</dt>
            <dd class="col-sm-9">{{ $voucher->garmentStyle?->style_number ?? '—' }} — {{ $voucher->garmentStyle?->name }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Process</dt>
            <dd class="col-sm-9">{{ \App\Models\ProductionOrder::JOB_WORK_TYPES[$voucher->process] ?? ($voucher->process ?: '—') }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Vehicle</dt>
            <dd class="col-sm-9">{{ $voucher->vehicle_no ?: '—' }}</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Pcs</dt>
            <dd class="col-sm-9">{{ number_format($voucher->total_qty) }} @if($voucher->isReceive()) ({{ $voucher->goodQty() }} good / {{ $voucher->damaged_qty }} damaged) @endif</dd>
            @if($voucher->isReceive())
                <dt class="col-sm-3 text-body-secondary fw-normal">Rate / pc</dt>
                <dd class="col-sm-9">{{ number_format((float) $voucher->rate_per_pc, 2) }}</dd>
                <dt class="col-sm-3 text-body-secondary fw-normal">Payable (good pcs)</dt>
                <dd class="col-sm-9 fw-semibold">₹ {{ number_format((float) $voucher->charge_amount, 2) }}</dd>
            @endif
            <dt class="col-sm-3 text-body-secondary fw-normal">Still with jobber</dt>
            <dd class="col-sm-9">{{ number_format($outstanding) }} pcs</dd>
            <dt class="col-sm-3 text-body-secondary fw-normal">Notes</dt>
            <dd class="col-sm-9">{{ $voucher->notes ?: '—' }}</dd>
        </dl>

        <h6 class="fw-semibold mb-2">Size-wise</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        @foreach(\App\Models\ProductionOrder::SIZES as $size)
                            <th class="text-center">{{ $size }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @foreach(\App\Models\ProductionOrder::SIZES as $size)
                            <td class="text-center">{{ $voucher->sizeQty($size) ?: '—' }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>

        @if($voucher->isReceive() && $voucher->damaged_qty > 0)
            <div class="card border mt-4">
                <div class="card-header bg-body fw-semibold">Debit note for damage</div>
                <div class="card-body">
                    @if($voucher->debitNotes->isNotEmpty())
                        <ul class="mb-0">
                            @foreach($voucher->debitNotes as $note)
                                <li>
                                    <a href="{{ route('finance.debit-notes.index') }}">{{ $note->debit_note_num }}</a>
                                    — ₹ {{ number_format((float) $note->amount, 2) }}
                                    ({{ $note->qty }} pcs)
                                </li>
                            @endforeach
                        </ul>
                    @elseif((float) $voucher->rate_per_pc > 0)
                        @can('debit-note.create')
                            <p class="small text-body-secondary mb-2">
                                {{ $voucher->damaged_qty }} damaged × ₹{{ number_format((float) $voucher->rate_per_pc, 2) }}
                                = ₹{{ number_format($voucher->damaged_qty * (float) $voucher->rate_per_pc, 2) }}
                            </p>
                            <form action="{{ route('finance.debit-notes.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="job_work_voucher_id" value="{{ $voucher->id }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Raise debit note for damage</button>
                            </form>
                        @else
                            <p class="small text-body-secondary mb-0">Ask accounts to raise a debit note for the damaged pieces.</p>
                        @endcan
                    @else
                        <p class="small text-body-secondary mb-0">Enter a rate per piece on the receive voucher to raise a debit note.</p>
                    @endif
                </div>
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
