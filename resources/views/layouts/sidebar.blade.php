@php
    $active = fn (string $pattern) => request()->routeIs($pattern) ? 'active' : '';
@endphp

<aside class="app-sidebar shadow-sm" data-enable-persistence="true">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-mark"><x-brand-logo :size="38" /></span>
            <span class="brand-text">
                Garment ERP
                <small>Apparel Manufacturing</small>
            </span>
        </a>
        <button type="button" class="sidebar-toggle" data-lte-toggle="sidebar"
                aria-label="Collapse sidebar" title="Collapse sidebar">
            <i class="bi bi-chevron-double-left"></i>
        </button>
    </div>

    <div class="sidebar-wrapper">
        <div class="sidebar-search px-3 pt-3 pb-2">
            <label class="visually-hidden" for="sidebar-search">Search menu</label>
            <div class="input-group input-group-sm sidebar-search-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="sidebar-search" class="form-control" placeholder="Search menu..." autocomplete="off">
                <span class="input-group-text sidebar-search-kbd">/</span>
            </div>
        </div>

        <nav>
            <ul class="nav sidebar-menu flex-column" role="menu">
                <li class="nav-item" data-nav-label="dashboard home">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ $active('dashboard') }}">
                        <i class="nav-icon bi bi-house-door"></i>
                        <p>Home</p>
                    </a>
                </li>

                <li class="nav-header">Masters</li>
                <li class="nav-item" data-nav-label="categories"><a href="{{ route('masters.categories.index') }}" class="nav-link {{ $active('masters.categories.*') }}"><i class="nav-icon bi bi-tags"></i><p>Categories</p></a></li>
                <li class="nav-item" data-nav-label="order formats"><a href="{{ route('masters.formats.index') }}" class="nav-link {{ $active('masters.formats.*') }}"><i class="nav-icon bi bi-file-earmark-ruled"></i><p>Order Formats</p></a></li>
                <li class="nav-item" data-nav-label="customers buyers"><a href="{{ route('masters.buyers.index') }}" class="nav-link {{ $active('masters.buyers.*') }}"><i class="nav-icon bi bi-person-badge"></i><p>Customers / Buyers</p></a></li>
                <li class="nav-item" data-nav-label="style master tech pack"><a href="{{ route('masters.styles.index') }}" class="nav-link {{ $active('masters.styles.*') }}"><i class="nav-icon bi bi-scissors"></i><p>Style Master &amp; Tech Pack</p></a></li>
                <li class="nav-item" data-nav-label="bom consumption"><a href="{{ route('masters.bom.index') }}" class="nav-link {{ $active('masters.bom.*') }}"><i class="nav-icon bi bi-calculator"></i><p>BOM &amp; Consumption</p></a></li>
                <li class="nav-item" data-nav-label="style costing cost sheet"><a href="{{ route('style-costings.index') }}" class="nav-link {{ $active('style-costings.*') }}"><i class="nav-icon bi bi-currency-rupee"></i><p>Style Costing</p></a></li>
                <li class="nav-item" data-nav-label="item master trims fabric"><a href="{{ route('masters.products.index') }}" class="nav-link {{ $active('masters.products.*') }}"><i class="nav-icon bi bi-box-seam"></i><p>Item Master (Trims/Fabric)</p></a></li>
                <li class="nav-item" data-nav-label="suppliers"><a href="{{ route('masters.suppliers.index') }}" class="nav-link {{ $active('masters.suppliers.*') }}"><i class="nav-icon bi bi-truck"></i><p>Suppliers Master</p></a></li>
                <li class="nav-item" data-nav-label="jobbers job worker"><a href="{{ route('masters.jobbers.index') }}" class="nav-link {{ $active('masters.jobbers.*') }}"><i class="nav-icon bi bi-building-gear"></i><p>Job Worker / Jobbers</p></a></li>
                <li class="nav-item" data-nav-label="fob values"><a href="{{ route('masters.fob-values.index') }}" class="nav-link {{ $active('masters.fob-values.*') }}"><i class="nav-icon bi bi-currency-dollar"></i><p>FOB Values</p></a></li>

                <li class="nav-header">Sales &amp; Orders</li>
                <li class="nav-item" data-nav-label="enquiry quotations"><a href="{{ route('sales.inquiries.index') }}" class="nav-link {{ $active('sales.inquiries.*') }}"><i class="nav-icon bi bi-chat-quote"></i><p>Enquiry &amp; Quotations</p></a></li>
                <li class="nav-item" data-nav-label="sales order po"><a href="{{ route('sales.order-confirmations.index') }}" class="nav-link {{ $active('sales.order-confirmations.*') }}"><i class="nav-icon bi bi-file-earmark-check"></i><p>Sales Order / PO</p></a></li>

                <li class="nav-header">Manufacturing Processes</li>
                <li class="nav-item" data-nav-label="work orders"><a href="{{ route('work-orders.index') }}" class="nav-link {{ $active('work-orders.*') }}"><i class="nav-icon bi bi-clipboard-check"></i><p>Work Orders</p></a></li>
                <li class="nav-item" data-nav-label="time and action tna"><a href="{{ route('time-and-action.index') }}" class="nav-link {{ $active('time-and-action.*') }}"><i class="nav-icon bi bi-calendar-week"></i><p>Time &amp; Action</p></a></li>
                <li class="nav-item" data-nav-label="production planning"><a href="{{ route('manufacturing.index') }}" class="nav-link {{ $active('manufacturing.index') || $active('manufacturing.create') || $active('manufacturing.show') || $active('manufacturing.edit') }}"><i class="nav-icon bi bi-diagram-3"></i><p>Production Planning</p></a></li>
                <li class="nav-item" data-nav-label="qc capa defect"><a href="{{ route('manufacturing.capa.index') }}" class="nav-link {{ $active('manufacturing.capa.*') }}"><i class="nav-icon bi bi-clipboard2-check"></i><p>QC CAPA</p></a></li>
                <li class="nav-item" data-nav-label="line efficiency sewing"><a href="{{ route('production-lines.index') }}" class="nav-link {{ $active('production-lines.*') }}"><i class="nav-icon bi bi-speedometer2"></i><p>Line efficiency</p></a></li>

                <li class="nav-header">Inventory &amp; Job Work</li>
                <li class="nav-item" data-nav-label="fabric accessory stock"><a href="{{ route('inventory.index') }}" class="nav-link {{ $active('inventory.*') }}"><i class="nav-icon bi bi-boxes"></i><p>Fabric &amp; Accessory Stock</p></a></li>
                <li class="nav-item" data-nav-label="fabric trims po"><a href="{{ route('procurement.purchase-orders.index') }}" class="nav-link {{ $active('procurement.purchase-orders.*') }}"><i class="nav-icon bi bi-cart-plus"></i><p>Fabric &amp; Trims PO</p></a></li>
                <li class="nav-item" data-nav-label="goods inward"><a href="{{ route('procurement.inward-entries.index') }}" class="nav-link {{ $active('procurement.inward-entries.*') }}"><i class="nav-icon bi bi-box-arrow-in-down"></i><p>Goods Inward</p></a></li>
                <li class="nav-item" data-nav-label="job work issue receive"><a href="{{ route('job-work.index') }}" class="nav-link {{ $active('job-work.*') }}"><i class="nav-icon bi bi-arrow-left-right"></i><p>Job Work Issue / Receive</p></a></li>

                <li class="nav-header">Packing, Shipment &amp; Billing</li>
                <li class="nav-item" data-nav-label="packing cartons"><a href="{{ route('export.packing.index') }}" class="nav-link {{ $active('export.packing.*') }}"><i class="nav-icon bi bi-box-seam"></i><p>Packing &amp; Cartons</p></a></li>
                <li class="nav-item" data-nav-label="export docs invoices"><a href="{{ route('export.documents.index') }}" class="nav-link {{ $active('export.documents.*') }}"><i class="nav-icon bi bi-file-earmark-pdf"></i><p>Export Docs &amp; Invoices</p></a></li>
                <li class="nav-item" data-nav-label="billing payments"><a href="{{ route('finance.purchase-bills.index') }}" class="nav-link {{ $active('finance.*') }}"><i class="nav-icon bi bi-cash-stack"></i><p>Billing &amp; Payments</p></a></li>

                <li class="nav-header">Intelligent OCR &amp; Reports</li>
                <li class="nav-item" data-nav-label="ocr document verification"><a href="{{ route('export.ocr.index') }}" class="nav-link {{ $active('export.ocr.*') }}"><i class="nav-icon bi bi-stars"></i><p>OCR Document Verification</p></a></li>
                <li class="nav-item" data-nav-label="erp reports outstanding"><a href="{{ route('reports.index') }}" class="nav-link {{ $active('reports.*') }}"><i class="nav-icon bi bi-bar-chart-line"></i><p>ERP Reports &amp; Outstanding</p></a></li>

                <li class="nav-header">Account</li>
                <li class="nav-item" data-nav-label="profile settings"><a href="{{ route('profile.edit') }}" class="nav-link {{ $active('profile.*') }}"><i class="nav-icon bi bi-person-circle"></i><p>Profile &amp; Settings</p></a></li>
                @can('user.view')
                    <li class="nav-item" data-nav-label="users"><a href="{{ route('user-management.users.index') }}" class="nav-link {{ $active('user-management.users.*') }}"><i class="nav-icon bi bi-people"></i><p>Users</p></a></li>
                @endcan
                @can('role.view')
                    <li class="nav-item" data-nav-label="roles"><a href="{{ route('user-management.roles.index') }}" class="nav-link {{ $active('user-management.roles.*') }}"><i class="nav-icon bi bi-shield-check"></i><p>Roles</p></a></li>
                @endcan
                @can('permission.view')
                    <li class="nav-item" data-nav-label="permissions"><a href="{{ route('user-management.permissions.index') }}" class="nav-link {{ $active('user-management.permissions.*') }}"><i class="nav-icon bi bi-key"></i><p>Permissions</p></a></li>
                @endcan
            </ul>
        </nav>
    </div>
</aside>
