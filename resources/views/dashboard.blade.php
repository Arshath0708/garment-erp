<x-app-layout>
    <x-slot name="header">Dashboard</x-slot>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner"><h3>150</h3><p>New Orders</p></div>
                <i class="small-box-icon bi bi-cart-fill"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner"><h3>53<sup class="fs-5">%</sup></h3><p>Bounce Rate</p></div>
                <i class="small-box-icon bi bi-bar-chart-fill"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner"><h3>44</h3><p>User Registrations</p></div>
                <i class="small-box-icon bi bi-person-plus-fill"></i>
                <a href="#" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner"><h3>65</h3><p>Unique Visitors</p></div>
                <i class="small-box-icon bi bi-pie-chart-fill"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">More info <i class="bi bi-link-45deg"></i></a>
            </div>
        </div>
    </div>
    <div class="row">
        <section class="col-lg-7">
            <div class="card card-primary card-outline mb-4">
                <div class="card-header"><h3 class="card-title"><i class="bi bi-currency-rupee me-1"></i>Revenue Growth</h3></div>
                <div class="card-body"><div id="revenue-chart"></div></div>
            </div>
        </section>
        <section class="col-lg-5">
            <div class="card card-success card-outline mb-4">
                <div class="card-header border-0"><h3 class="card-title"><i class="bi bi-box-seam me-1"></i>Top Selling Products</h3></div>
                <div class="card-body"><div id="product-chart"></div></div>
            </div>
        </section>
    </div>
    <script type="module">
        const revenueOptions = {
            series: [{ name: 'Revenue (₹)', data: [31000, 40000, 28000, 51000, 42000, 109000, 100000] }],
            chart: { height: 300, type: 'area', toolbar: { show: false } },
            colors: ['#0d6efd'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth' },
            xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'] }
        };
        const revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), revenueOptions);
        revenueChart.render();
        const productOptions = {
            series: [{ name: 'Quantity Sold', data: [400, 300, 200, 150] }],
            chart: { type: 'bar', height: 300, toolbar: { show: false } },
            colors: ['#198754', '#ffc107', '#dc3545', '#0dcaf0'],
            plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true } },
            dataLabels: { enabled: false },
            xaxis: { categories: ['Electronics', 'Furniture', 'Stationery', 'Clothing'] },
            legend: { show: false }
        };
        const productChart = new ApexCharts(document.querySelector("#product-chart"), productOptions);
        productChart.render();
    </script>
</x-app-layout>
