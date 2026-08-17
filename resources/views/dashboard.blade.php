<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>

    {{-- Top Statistics Cards --}}
    <div class="row">
        {{-- Card 1: Inquiries --}}
        <div class="col-md-6 col-12 mb-3">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ number_format($inquiryCount) }}</h3>
                    <p>Total Inquiries</p>
                </div>
                <i class="small-box-icon bi bi-inbox-fill"></i>
                @can('inquiry.view')
                    <a href="{{ route('sales.inquiries.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        View Inquiries <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75">All Inquiries</span>
                @endcan
            </div>
        </div>

        {{-- Card 2: Order Confirmations --}}
        <div class="col-md-6 col-12 mb-3">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ number_format($orderConfirmationCount) }}</h3>
                    <p>Order Confirmations</p>
                </div>
                <i class="small-box-icon bi bi-cart-check-fill"></i>
                @can('order_confirmation.view')
                    <a href="{{ route('sales.order-confirmations.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                        View Order Confirmations <i class="bi bi-arrow-right-circle-fill ms-1"></i>
                    </a>
                @else
                    <span class="small-box-footer link-light opacity-75">All Confirmations</span>
                @endcan
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="row">
        {{-- Chart 1: Inquiry vs Order Confirmation Trend --}}
        <section class="col-lg-7">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-graph-up-arrow me-1"></i>Order Flow Trend (6 Months)
                    </h3>
                </div>
                <div class="card-body">
                    <div id="order-flow-chart" style="min-height: 310px;"></div>
                </div>
            </div>
        </section>

        {{-- Chart 2: Inquiry Status Breakdown --}}
        <section class="col-lg-5">
            <div class="card card-success card-outline mb-4">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-pie-chart-fill me-1"></i>Inquiry Status Distribution
                    </h3>
                </div>
                <div class="card-body">
                    @if(empty($inquiryStatusData) || array_sum($inquiryStatusData) === 0)
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2 text-opacity-50"></i>
                            <p class="mb-0 fs-6">No inquiry status records found yet.</p>
                        </div>
                    @else
                        <div id="inquiry-status-chart" style="min-height: 310px;"></div>
                    @endif
                </div>
            </div>
        </section>
    </div>

    @push('scripts')
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
                        }
                    ],
                    chart: {
                        height: 310,
                        type: 'area',
                        toolbar: { show: false },
                        fontFamily: 'inherit'
                    },
                    colors: ['#0d6efd', '#198754'],
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
