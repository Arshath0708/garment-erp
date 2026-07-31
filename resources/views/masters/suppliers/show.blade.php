@php
    use App\Models\Supplier;
@endphp

<x-app-layout>
    <x-slot name="header">Supplier Details</x-slot>

    <x-ui.card title="{{ $supplier->display_code }} — {{ $supplier->company_name }}" variant="primary">
        <x-slot name="actions">
            @can('supplier.edit')
                <a href="{{ route('masters.suppliers.edit', $supplier) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            <a href="{{ route('masters.suppliers.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
        </x-slot>

        <div class="row g-4">
            <div class="col-lg-7">
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-body-secondary fw-normal">Display Code</dt>
                    <dd class="col-sm-7"><span class="badge text-bg-light border font-monospace">{{ $supplier->display_code }}</span></dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Party Type</dt>
                    <dd class="col-sm-7">{{ Supplier::PARTY_TYPES[$supplier->party_type] }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Company Name</dt>
                    <dd class="col-sm-7 fw-semibold">{{ $supplier->company_name }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Name on Bill</dt>
                    <dd class="col-sm-7">{{ $supplier->name_on_bill ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Product Category</dt>
                    <dd class="col-sm-7">
                        @forelse($supplier->categories as $category)
                            <span class="badge text-bg-light border me-1 mb-1">{{ $category->name }}</span>
                        @empty
                            —
                        @endforelse
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Status</dt>
                    <dd class="col-sm-7"><x-ui.status-badge :status="$supplier->status === 'active'" /></dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Supplier Type</dt>
                    <dd class="col-sm-7">{{ $supplier->supplierType?->name ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">GST Number</dt>
                    <dd class="col-sm-7 font-monospace">{{ $supplier->gst_number ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">PAN Number</dt>
                    <dd class="col-sm-7 font-monospace">{{ $supplier->pan_number ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">MSME</dt>
                    <dd class="col-sm-7">
                        @if($supplier->is_msme)
                            <span class="font-monospace">{{ $supplier->msme_registration_no ?: 'Registered' }}</span>
                        @else
                            Not registered
                        @endif
                    </dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Address</dt>
                    <dd class="col-sm-7">{{ $supplier->address ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">City / State</dt>
                    <dd class="col-sm-7">{{ collect([$supplier->city?->name, $supplier->state?->name])->filter()->implode(', ') ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Country</dt>
                    <dd class="col-sm-7">{{ $supplier->country?->name ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">PIN Code</dt>
                    <dd class="col-sm-7">{{ $supplier->pincode ?: '—' }}</dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Discount %</dt>
                    <dd class="col-sm-7">{{ $supplier->discount_percent !== null ? $supplier->discount_percent.'%' : '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Credit Terms</dt>
                    <dd class="col-sm-7">{{ $supplier->credit_terms_label ?: '—' }}</dd>

                    {{-- Jobwork-only, so shown only where it means something.
                         Printing "We supply material: No" against a trading
                         supplier states a fact that was never in question. --}}
                    @if($supplier->does_jobwork)
                        <dt class="col-sm-5 text-body-secondary fw-normal">We Supply Material</dt>
                        <dd class="col-sm-7">{{ $supplier->we_supply_material ? 'Yes' : 'No' }}</dd>

                        <dt class="col-sm-5 text-body-secondary fw-normal">Sample Approval</dt>
                        <dd class="col-sm-7">{{ $supplier->requires_sample_approval ? 'Required before PO' : 'Not required' }}</dd>
                    @endif

                    <dt class="col-sm-5 text-body-secondary fw-normal">Delivery Mode</dt>
                    <dd class="col-sm-7">{{ Supplier::DELIVERY_MODES[$supplier->default_delivery_mode] }}</dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Bank Name</dt>
                    <dd class="col-sm-7">{{ $supplier->bank_name ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Account Number</dt>
                    <dd class="col-sm-7 font-monospace">{{ $supplier->account_number ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">IFSC Code</dt>
                    <dd class="col-sm-7 font-monospace">{{ $supplier->ifsc_code ?: '—' }}</dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Agent</dt>
                    <dd class="col-sm-7">{{ $supplier->agent?->label ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Agent Commission</dt>
                    <dd class="col-sm-7">{{ $supplier->agent_commission_label ?: '—' }}</dd>

                    <dt class="col-sm-12 pt-3"><hr class="my-2"></dt>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Remarks</dt>
                    <dd class="col-sm-7">{{ $supplier->remarks ?: '—' }}</dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Created</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $supplier->created_at?->format('d M Y, H:i') }}
                        @if($supplier->creator) by {{ $supplier->creator->name }} @endif
                    </dd>

                    <dt class="col-sm-5 text-body-secondary fw-normal">Last updated</dt>
                    <dd class="col-sm-7 text-body-secondary small">
                        {{ $supplier->updated_at?->format('d M Y, H:i') }}
                        @if($supplier->updater) by {{ $supplier->updater->name }} @endif
                    </dd>
                </dl>
            </div>

            {{-- Sheet cols H–K and L. One list, primary first — which is how the
                 rows are stored, so this is not a merge done for display. --}}
            <div class="col-lg-5">
                <div class="card border">
                    <div class="card-header bg-body-tertiary py-2">
                        <span class="small fw-semibold text-body-secondary text-uppercase">Contacts</span>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse($supplier->contacts as $contact)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ $contact->name }}</div>
                                        @if($contact->designation)
                                            <div class="small text-body-secondary">{{ $contact->designation->name }}</div>
                                        @endif
                                    </div>
                                    @if($contact->is_primary)
                                        <span class="badge text-bg-primary-subtle text-primary-emphasis border">Primary</span>
                                    @endif
                                </div>

                                @if($contact->mobile)
                                    <div class="small mt-1"><i class="bi bi-telephone me-1"></i>{{ $contact->mobile }}</div>
                                @endif
                                @if($contact->email)
                                    <div class="small"><i class="bi bi-envelope me-1"></i><a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a></div>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-body-secondary fst-italic">No contacts recorded.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>
</x-app-layout>
