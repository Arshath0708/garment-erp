<x-app-layout>
    <x-slot name="header">Job Work Issue / Receive</x-slot>

    <x-ui.card title="Job Work Issue / Receive" variant="primary">
        <x-slot name="actions">
            @can('job-work.create')
                <a href="{{ route('job-work.create', ['type' => 'issue']) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-box-arrow-up me-1"></i> Issue
                </a>
                <a href="{{ route('job-work.create', ['type' => 'receive']) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Receive
                </a>
            @endcan
        </x-slot>

        <p class="text-body-secondary small mb-3">
            Send pieces to a jobber, then record what came back — including damage. Outstanding = issued − received.
        </p>

        <form method="GET" class="row g-2 align-items-end mb-3">
            <div class="col-md-5">
                <label class="form-label small text-body-secondary mb-1">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="form-control form-control-sm" placeholder="JW no., jobber, production order">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach(\App\Models\JobWorkVoucher::TYPES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-secondary">Filter</button>
                <a href="{{ route('job-work.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Jobber</th>
                        <th>Production</th>
                        <th class="text-end">Pcs</th>
                        <th class="text-end">Damage</th>
                        <th class="text-end">Payable</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $voucher)
                        <tr>
                            <td class="font-monospace">{{ $voucher->voucher_num }}</td>
                            <td>{{ $voucher->voucher_date?->format('d M Y') }}</td>
                            <td><span class="badge text-bg-{{ $voucher->typeColor() }}">{{ $voucher->typeLabel() }}</span></td>
                            <td>{{ $voucher->jobber?->company_name ?? '—' }}</td>
                            <td>{{ $voucher->productionOrder?->order_number ?? '—' }}</td>
                            <td class="text-end">{{ number_format($voucher->total_qty) }}</td>
                            <td class="text-end">{{ $voucher->damaged_qty ?: '—' }}</td>
                            <td class="text-end">{{ $voucher->isReceive() && (float) $voucher->charge_amount > 0 ? number_format((float) $voucher->charge_amount, 2) : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('job-work.show', $voucher) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.empty-state colspan="9"
                                          icon="bi-arrow-left-right"
                                          title="No job-work vouchers yet"
                                          message="Issue pieces to a jobber, then receive them back." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($vouchers->hasPages())
            <div class="mt-3">{{ $vouchers->links() }}</div>
        @endif
    </x-ui.card>
</x-app-layout>
