<x-app-layout>
    <x-slot name="header">Bill of Materials (BOM) & Material Consumption</x-slot>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <p class="text-body-secondary small mb-0">Automated calculation: <strong>Order Qty × Consumption = Required Material</strong> (Section 4 Rule)</p>
        </div>
    </div>

    <!-- Sample BOM Calculator Card -->
    <div class="card shadow-sm border-0 mb-4 bg-body p-4 border-start border-primary border-4">
        <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-calculator me-2"></i> Section 4 BOM Automated Requirement Calculator (Sample Order: ST-1005 — 10,000 pcs)</h5>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Material Description</th>
                        <th>Material Type</th>
                        <th>Consumption / pc</th>
                        <th>Qty for 10,000 pcs</th>
                        <th>Available Stock</th>
                        <th>Purchase Requirement</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold">Main Fabric (Cotton Twill)</td>
                        <td><span class="badge bg-info text-dark">Fabric</span></td>
                        <td>1.20 kg</td>
                        <td class="fw-bold text-primary">12,000 kg</td>
                        <td>8,000 kg</td>
                        <td class="fw-bold text-danger">4,000 kg</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Collar Interlining</td>
                        <td><span class="badge bg-secondary">Trims</span></td>
                        <td>1 pc</td>
                        <td class="fw-bold text-primary">10,000 pcs</td>
                        <td>10,000 pcs</td>
                        <td class="fw-bold text-success">0 pcs (Sufficient)</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Resin Buttons</td>
                        <td><span class="badge bg-secondary">Trims</span></td>
                        <td>3 pcs</td>
                        <td class="fw-bold text-primary">30,000 pcs</td>
                        <td>15,000 pcs</td>
                        <td class="fw-bold text-danger">15,000 pcs</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Sewing Thread (Spun Poly)</td>
                        <td><span class="badge bg-secondary">Trims</span></td>
                        <td>20 gm</td>
                        <td class="fw-bold text-primary">200 kg</td>
                        <td>50 kg</td>
                        <td class="fw-bold text-danger">150 kg</td>
                    </tr>
                    <tr>
                        <td class="fw-bold">Woven Brand Label</td>
                        <td><span class="badge bg-secondary">Labels</span></td>
                        <td>1 pc</td>
                        <td class="fw-bold text-primary">10,000 pcs</td>
                        <td>12,000 pcs</td>
                        <td class="fw-bold text-success">0 pcs (Sufficient)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Style Masters BOM Listing -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-body fw-bold py-3 border-0">
            <i class="bi bi-layers me-2 text-primary"></i> Style Masters Bill of Materials
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Style #</th>
                        <th>Style Name</th>
                        <th>Buyer</th>
                        <th>Target Qty</th>
                        <th>BOM Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($styles as $style)
                        <tr>
                            <td class="fw-bold text-primary">{{ $style->style_number }}</td>
                            <td>{{ $style->name }}</td>
                            <td>{{ $style->buyer ? $style->buyer->company_name : '—' }}</td>
                            <td>{{ number_format($style->target_qty) }} pcs</td>
                            <td><span class="badge bg-success">Configured</span></td>
                            <td class="text-end">
                                <a href="{{ route('masters.styles.show', $style) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View Tech Pack & BOM
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-body-secondary">No styles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
