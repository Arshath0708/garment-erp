<x-app-layout>
    <x-slot name="header">Garment Manufacturing Overview</x-slot>

    {{-- SECTION 1: TOP EXECUTIVE STATISTICS (Guru Traders Style) --}}
    <div class="row mb-2">
        {{-- Card 1: Inquiries --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-primary shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">{{ number_format($inquiryCount) }}</h3>
                    <p class="mb-0">Total Inquiries</p>
                </div>
                <i class="small-box-icon bi bi-inbox-fill opacity-50"></i>
                @can('inquiry.view')
                    <a href="{{ route('sales.inquiries.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Inquiries <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">All Inquiries</span>
                @endcan
            </div>
        </div>

        {{-- Card 2: Order Confirmations --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-success shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">{{ number_format($orderConfirmationCount) }}</h3>
                    <p class="mb-0">Order Confirmations</p>
                </div>
                <i class="small-box-icon bi bi-cart-check-fill opacity-50"></i>
                @can('order-confirmation.view')
                    <a href="{{ route('sales.order-confirmations.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Order Confirmations <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">All Confirmations</span>
                @endcan
            </div>
        </div>

        {{-- Card 3: Purchase Orders --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-info shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">{{ number_format($purchaseOrderCount) }}</h3>
                    <p class="mb-0">Purchase Orders</p>
                </div>
                <i class="small-box-icon bi bi-cart3 opacity-50"></i>
                @can('purchase-order.view')
                    <a href="{{ route('procurement.purchase-orders.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Purchase Orders <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">All Purchase Orders</span>
                @endcan
            </div>
        </div>

        {{-- Card 4: Open Shipments --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-warning shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">{{ number_format($openShipmentCount) }}</h3>
                    <p class="mb-0">Open Shipments</p>
                </div>
                <i class="small-box-icon bi bi-truck opacity-50"></i>
                @can('export-document.view')
                    <a href="{{ route('export.documents.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Export Documents <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">All Shipments</span>
                @endcan
            </div>
        </div>

        {{-- Card 5: Buyer Outstanding --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-danger shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">${{ number_format($buyerOutstanding, 2) }}</h3>
                    <p class="mb-0">Buyer Outstanding</p>
                </div>
                <i class="small-box-icon bi bi-cash-stack opacity-50"></i>
                @can('outstanding.view')
                    <a href="{{ route('reports.outstanding.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Outstanding <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">Across Export Documents</span>
                @endcan
            </div>
        </div>

        {{-- Card 6: Supplier Outstanding --}}
        <div class="col-xl-4 col-md-6 col-12 mb-3">
            <div class="small-box text-bg-dark shadow-sm rounded-3">
                <div class="inner p-3">
                    <h3 class="fw-bold mb-1">${{ number_format($supplierOutstanding, 2) }}</h3>
                    <p class="mb-0">Supplier Outstanding</p>
                </div>
                <i class="small-box-icon bi bi-building opacity-50"></i>
                @can('outstanding.view')
                    <a href="{{ route('reports.outstanding.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover py-2 d-block px-3">
                        View Outstanding <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75 py-2 d-block px-3">Across Purchase Orders</span>
                @endcan
            </div>
        </div>
    </div>

    {{-- SECTION 2: CHARTS (Guru Traders Style Trend & Donut Charts) --}}
    <div class="row mb-4">
        {{-- Chart 1: Pipeline Trend (6 Months) --}}
        <section class="col-lg-7 mb-3 mb-lg-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i> Pipeline Trend (6 Months)
                    </h5>
                </div>
                <div class="card-body">
                    <div id="order-flow-chart" style="min-height: 310px;"></div>
                </div>
            </div>
        </section>

        {{-- Chart 2: Inquiry Status Breakdown --}}
        <section class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">
                        <i class="bi bi-pie-chart-fill text-success me-2"></i> Inquiry Status Distribution
                    </h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    @if(empty($inquiryStatusData) || array_sum($inquiryStatusData) === 0)
                        <div class="text-center py-5 text-body-secondary">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-opacity-50"></i>
                            <p class="mb-0 fs-6">No inquiry status records found yet.</p>
                        </div>
                    @else
                        <div id="inquiry-status-chart" class="w-100" style="min-height: 310px;"></div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    {{-- SECTION 3: TODAY'S GARMENT PRODUCTION --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-activity text-primary me-2"></i> Today's Garment Production</h4>
                <div class="fs-5 text-primary fw-bold">PO-00452 — 10,000 pcs</div>
            </div>
            <a href="{{ route('manufacturing.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-gear-wide-connected me-1"></i> Open Manufacturing Desk
            </a>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Cutting 85% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-scissors text-primary me-1"></i> Cutting</span>
                            <span class="badge bg-primary fs-6">85%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 85%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 8,500 pcs</span>
                            <span>Pending: 1,500 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Printing 82% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-printer text-info me-1"></i> Printing</span>
                            <span class="badge bg-info text-dark fs-6">82%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: 82%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 8,200 pcs</span>
                            <span>Pending: 1,800 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Stitching 65% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-gear-wide-connected text-secondary me-1"></i> Stitching</span>
                            <span class="badge bg-secondary fs-6">65%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-secondary" role="progressbar" style="width: 65%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 6,500 pcs</span>
                            <span>Pending: 3,500 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Finishing 58% (Attention Required) -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-warning-emphasis"><i class="bi bi-shield-exclamation text-warning me-1"></i> 🟠 Finishing</span>
                            <span class="badge bg-warning text-dark fs-6">58%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: 58%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 5,800 pcs</span>
                            <span>Pending: 4,200 pcs</span>
                        </div>
                    </div>
                </div>

                <!-- Packing 55% -->
                <div class="col-md-6 col-lg">
                    <div class="p-3 bg-body-tertiary rounded border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-body"><i class="bi bi-box-seam text-success me-1"></i> Packing</span>
                            <span class="badge bg-success fs-6">55%</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 55%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-body-secondary small">
                            <span>Completed: 5,500 pcs</span>
                            <span>Pending: 4,500 pcs</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECTION 4: IMPORTANT ALERTS (EXCEPTION MONITOR) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0 text-danger"><i class="bi bi-bell-fill me-2"></i> Important Alerts</h4>
            <span class="badge bg-danger">Exception Monitor</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <!-- Alert 1 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">1,500 pcs cutting pending</div>
                            <span class="text-body-secondary small">PO-00452 cutting delay</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 2 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">2,000 pcs stitching pending</div>
                            <span class="text-body-secondary small">Jobber Line 3 delay</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 3 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Fabric shortage for PO-00453</div>
                            <span class="text-body-secondary small">Woven Twill stock low</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 4 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Jobber material pending</div>
                            <span class="text-body-secondary small">Outsourced printing unit</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 5 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-x-circle-fill text-danger fs-3"></i>
                        <div>
                            <div class="fw-bold text-danger">Invoice quantity mismatch</div>
                            <span class="text-body-secondary small">OCR document checker alert</span>
                        </div>
                    </div>
                </div>

                <!-- Alert 6 -->
                <div class="col-md-6 col-lg-4">
                    <div class="p-3 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded d-flex align-items-center gap-3">
                        <i class="bi bi-exclamation-triangle-fill text-warning fs-3"></i>
                        <div>
                            <div class="fw-bold text-body">Shipment due in 3 days</div>
                            <span class="text-body-secondary small">Container loading scheduled</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;

            // Trend Chart Options
            const trendChartEl = document.querySelector("#order-flow-chart");
            if (trendChartEl) {
                const trendOptions = {
                    series: [
                        {
                            name: 'Inquiries Raised',
                            data: @json($inquirySeriesData)
                        },
                        {
                            name: 'Order Confirmations',
                            data: @json($ocSeriesData)
                        },
                        {
                            name: 'Purchase Orders',
                            data: @json($poSeriesData)
                        },
                        {
                            name: 'Export Documents',
                            data: @json($exportDocSeriesData)
                        }
                    ],
                    chart: {
                        height: 310,
                        type: 'area',
                        toolbar: { show: false },
                        fontFamily: 'inherit'
                    },
                    colors: ['#0d6efd', '#198754', '#fd7e14', '#6f42c1'],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: {
                        type: 'gradient',
                        gradient: {
                            shadeIntensity: 1,
                            opacityFrom: 0.45,
                            opacityTo: 0.05,
                            stops: [0, 90, 100]
                        }
                    },
                    xaxis: {
                        categories: @json($chartLabels),
                        labels: { style: { colors: '#6c757d' } }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: '#6c757d' },
                            formatter: function (val) { return Math.floor(val); }
                        },
                        min: 0,
                        forceNiceScale: true
                    },
                    tooltip: {
                        shared: true,
                        intersect: false
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right'
                    }
                };

                const trendChart = new ApexCharts(trendChartEl, trendOptions);
                trendChart.render();
            }

            // Inquiry Status Breakdown Chart
            const statusChartEl = document.querySelector("#inquiry-status-chart");
            const statusData = @json($inquiryStatusData);

            if (statusChartEl && statusData.length > 0) {
                const statusOptions = {
                    series: statusData,
                    labels: @json($inquiryStatusLabels),
                    chart: {
                        type: 'donut',
                        height: 310,
                        fontFamily: 'inherit'
                    },
                    colors: ['#6c757d', '#0dcaf0', '#ffc107', '#198754', '#0d6efd', '#dc3545'],
                    legend: {
                        position: 'bottom'
                    },
                    responsive: [{
                        breakpoint: 480,
                        options: {
                            chart: { width: '100%' },
                            legend: { position: 'bottom' }
                        }
                    }]
                };

                const statusChart = new ApexCharts(statusChartEl, statusOptions);
                statusChart.render();
            }
        });
    </script>
    @endpush
</x-app-layout>
