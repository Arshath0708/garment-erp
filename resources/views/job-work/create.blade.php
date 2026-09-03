<x-app-layout>
    <x-slot name="header">{{ $type === 'receive' ? 'Receive from jobber' : 'Issue to jobber' }}</x-slot>

    <x-ui.card :title="$type === 'receive' ? 'Receive from jobber' : 'Issue to jobber'" variant="primary">
        <form action="{{ route('job-work.store') }}" method="POST">
            @csrf
            <input type="hidden" name="type" value="{{ $type }}">

            <x-ui.form-section title="{{ $type === 'receive' ? 'What came back' : 'What we are sending' }}" icon="bi-arrow-left-right"
                               subtitle="Size-wise pcs. Receive cannot exceed what is still outstanding with the jobber.">
                <div class="row">
                    <x-ui.field name="voucher_date" label="Date" type="date" required col="col-md-3"
                                :value="old('voucher_date', now()->format('Y-m-d'))" />
                    <div class="col-md-5 mb-3">
                        <label class="form-label fw-semibold required">Production order</label>
                        <select name="production_order_id" class="form-select @error('production_order_id') is-invalid @enderror"
                                onchange="if (this.value) { window.location = '{{ route('job-work.create') }}?type={{ $type }}&production_order_id=' + this.value; }">
                            <option value="">— Select —</option>
                            @foreach($orders as $order)
                                <option value="{{ $order->id }}" @selected((string) old('production_order_id', $selectedOrder?->id) === (string) $order->id)>
                                    {{ $order->order_number }}
                                    @if($order->garmentStyle) — {{ $order->garmentStyle->style_number }} @endif
                                    ({{ number_format($order->total_qty) }} pcs)
                                </option>
                            @endforeach
                        </select>
                        @error('production_order_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if($selectedOrder)
                            <div class="form-text">
                                Style {{ $selectedOrder->garmentStyle?->style_number ?? '—' }}
                                · outstanding with jobber: <strong>{{ number_format($outstanding) }}</strong> pcs
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold required">Jobber</label>
                        <select name="jobber_id" class="form-select @error('jobber_id') is-invalid @enderror" required>
                            <option value="">— Select —</option>
                            @foreach($jobbers as $jobber)
                                <option value="{{ $jobber->id }}" @selected((string) old('jobber_id', $selectedOrder?->jobber_id) === (string) $jobber->id)>
                                    {{ $jobber->company_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('jobber_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Process</label>
                        <select name="process" class="form-select">
                            <option value="">—</option>
                            @foreach(\App\Models\ProductionOrder::JOB_WORK_TYPES as $value => $label)
                                @if($value === 'in_house') @continue @endif
                                <option value="{{ $value }}" @selected(old('process', $selectedOrder?->job_work_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-ui.field name="vehicle_no" label="Vehicle no." col="col-md-4"
                                :value="old('vehicle_no', $selectedOrder?->vehicle_no)" />
                    @if($type === 'receive')
                        <x-ui.field name="damaged_qty" label="Damaged pcs" type="number" col="col-md-2"
                                    :value="old('damaged_qty', 0)" min="0"
                                    hint="Good pcs = received − damaged." />
                        <x-ui.field name="rate_per_pc" label="Rate / pc (₹)" type="number" col="col-md-2"
                                    :value="old('rate_per_pc', 0)" min="0" step="0.01"
                                    hint="Payable = good pcs × rate. Debit note uses damage × rate." />
                    @endif
                </div>

                @error('sizes')<div class="alert alert-danger py-2 small">{{ $message }}</div>@enderror

                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle mb-0">
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
                                    <td>
                                        <input type="number" min="0" class="form-control form-control-sm text-end"
                                               name="sizes[{{ $size }}]" value="{{ old('sizes.'.$size, 0) }}">
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <x-ui.textarea name="notes" label="Notes" col="col-12" rows="2" :value="old('notes')" />
                </div>
            </x-ui.form-section>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary px-4">
                    {{ $type === 'receive' ? 'Record receive' : 'Record issue' }}
                </button>
                <a href="{{ route('job-work.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
