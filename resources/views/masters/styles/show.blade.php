<x-app-layout>
    <x-slot name="header">Tech Pack — {{ $style->style_number }}</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h2 class="h4 fw-bold mb-0">{{ $style->style_number }} — {{ $style->name }}</h2>
                <span class="badge bg-success">{{ $style->status }}</span>
            </div>
            <p class="text-body-secondary small mb-0">Buyer: <strong>{{ $style->buyer ? $style->buyer->company_name : 'N/A' }}</strong> | Category: <strong>{{ $style->category ? $style->category->name : 'N/A' }}</strong></p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('masters.styles.edit', $style) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil-square me-1"></i> Edit Style
            </a>
            <form action="{{ route('masters.styles.destroy', $style) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this style?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i> Delete
                </button>
            </form>
            <a href="{{ route('masters.styles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Styles
            </a>
        </div>

    </div>

    <div class="row g-4 mb-4">
        <!-- Style Key Summary Cards -->
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-body-tertiary p-3">
                <div class="text-body-secondary small text-uppercase">Buyer Style No.</div>
                <div class="fw-bold fs-6 text-primary">{{ $style->buyer_style_no ?: '—' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-body-tertiary p-3">
                <div class="text-body-secondary small text-uppercase">Factory Style No.</div>
                <div class="fw-bold fs-6 text-dark">{{ $style->factory_style_no ?: '—' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-body-tertiary p-3">
                <div class="text-body-secondary small text-uppercase">Fabric & GSM</div>
                <div class="fw-bold fs-6 text-primary">{{ $style->fabric ?: 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-body-tertiary p-3">
                <div class="text-body-secondary small text-uppercase">Target Batch Qty</div>
                <div class="fw-bold fs-6 text-success">{{ number_format($style->target_qty) }} pcs</div>
            </div>
        </div>
    </div>

    <!-- Tech Pack Specs & Construction -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-body fw-bold py-3 border-0">
                    <i class="bi bi-journal-text me-2 text-primary"></i> Tech Pack Technical Specifications
                </div>
                <div class="card-body">
                    <p class="mb-0 text-body-secondary" style="white-space: pre-line;">{{ $style->tech_specs ?: 'No additional technical specifications provided for this style.' }}</p>
                </div>
            </div>

            <!-- Style / Tech Pack Comments -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-body fw-bold py-3 border-0 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-chat-left-text me-2 text-warning"></i> Style &amp; Tech Pack Comments</span>
                    <span class="badge bg-secondary">{{ $style->comments->count() }} Comments</span>
                </div>
                <div class="card-body">
                    <form action="{{ route('masters.styles.comments.store', $style) }}" method="POST" class="mb-4">
                        @csrf
                        <div class="mb-2">
                            <textarea name="comment" class="form-control" rows="2" placeholder="Add a buyer instruction or tech pack comment..." required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-send me-1"></i> Add Comment
                            </button>
                        </div>
                    </form>

                    <div class="comment-history">
                        @forelse($style->comments as $c)
                            <div class="p-3 bg-body-tertiary rounded border mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-primary small">{{ $c->user_name }}</strong>
                                    <small class="text-body-secondary"><i class="bi bi-clock me-1"></i>{{ $c->created_at->format('Y-m-d H:i') }}</small>
                                </div>
                                <div class="text-body small" style="white-space: pre-line;">{{ $c->comment }}</div>
                            </div>
                        @empty
                            <p class="text-body-secondary small mb-0">No comments added to this style yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-body fw-bold py-3 border-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                        <i class="bi bi-box-seam me-2 text-primary"></i> Fabric &amp; accessories BOM
                        <span class="badge text-bg-light border ms-1">v{{ $style->bom_version ?: 1 }}</span>
                        @if($style->isBomApproved())
                            <span class="badge text-bg-success">Approved {{ $style->bom_approved_at?->format('d M Y') }}{{ $style->bomApprover ? ' · '.$style->bomApprover->name : '' }}</span>
                        @else
                            <span class="badge text-bg-secondary">Not approved</span>
                        @endif
                    </span>
                    @if(! $style->isBomApproved())
                        <form action="{{ route('masters.styles.approve-bom', $style) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success">Approve BOM</button>
                        </form>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Sizes</th>
                                <th class="text-end">Qty / pc</th>
                                <th>Unit</th>
                                <th class="text-end">In stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($style->materials as $line)
                                <tr>
                                    <td>{{ $line->product?->name ?? '—' }}</td>
                                    <td>{{ \App\Models\Product::KINDS[$line->product->item_kind ?? 'other'] ?? '' }}</td>
                                    <td class="small">{{ $line->sizeRangeLabel() }}</td>
                                    <td class="text-end">{{ number_format((float) $line->qty_per_pc, 4) }}</td>
                                    <td>{{ $line->unit ?: $line->product?->unit_po }}</td>
                                    <td class="text-end">{{ number_format((float) ($line->product?->qty_on_hand ?? 0), 3) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-body-secondary text-center py-3">No BOM yet. Edit the style to add fabric and accessories.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Associated Production Orders -->
            <div class="card shadow-sm border-0">
                <div class="card-header bg-body fw-bold py-3 border-0 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-gear-wide-connected me-2 text-info"></i> Associated Manufacturing & Production Orders</span>
                    <a href="{{ route('manufacturing.create') }}" class="btn btn-sm btn-primary">Start New Production Order</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Production Order #</th>
                                <th>Total Order Qty</th>
                                <th>Current Stage</th>
                                <th>Target Date</th>
                                <th>Progress Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($style->productionOrders as $prodOrder)
                                <tr>
                                    <td class="fw-bold text-primary">{{ $prodOrder->order_number }}</td>
                                    <td class="fw-bold">{{ number_format($prodOrder->total_qty) }} pcs</td>
                                    <td><span class="badge bg-warning text-dark">{{ $prodOrder->current_stage }}</span></td>
                                    <td>{{ $prodOrder->target_date ? $prodOrder->target_date->format('Y-m-d') : '—' }}</td>
                                    <td><span class="badge bg-primary">{{ $prodOrder->status }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-body-secondary py-3">No active production orders linked to this style yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @can('style-costing.view')
                @php $approvedCosting = $style->latestApprovedCosting(); @endphp
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold text-body-secondary mb-2">Approved cost / pc</h6>
                        @if($approvedCosting)
                            <p class="display-6 fw-bold text-primary mb-2">₹ {{ number_format((float) $approvedCosting->total_cost_per_pc, 2) }}</p>
                            <p class="small text-body-secondary mb-3">
                                {{ $approvedCosting->costing_num }}
                                · signed {{ $approvedCosting->approved_at?->format('d M Y') }}
                            </p>
                            <a href="{{ route('style-costings.show', $approvedCosting) }}" class="btn btn-sm btn-outline-primary">
                                Open costing sheet
                            </a>
                        @else
                            <p class="text-body-secondary small mb-3">No signed costing yet. BOM qty × rate plus cut-make.</p>
                        @endif
                        @can('style-costing.create')
                            <a href="{{ route('style-costings.create', ['style_id' => $style->id]) }}" class="btn btn-sm btn-primary {{ $approvedCosting ? 'ms-1' : '' }}">
                                {{ $approvedCosting ? 'New costing' : 'Cost this style' }}
                            </a>
                        @endcan
                        <p class="small text-body-secondary mt-3 mb-0">Ready FG stock:
                            <strong>{{ number_format($style->stock?->qty_on_hand ?? 0) }}</strong> pcs
                        </p>
                    </div>
                </div>
            @endcan
            <div class="card shadow-sm border-0 p-3 text-center">
                <h6 class="fw-bold text-body-secondary mb-3">Garment Reference / Logo</h6>
                @if ($style->logo_path)
                    <img src="{{ asset('storage/' . $style->logo_path) }}" class="img-fluid rounded border" alt="Style Image">
                @else
                    <div class="p-5 bg-body-tertiary rounded text-body-secondary">
                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                        No reference image uploaded.
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
