@php
    $open = fn (string $pattern) => request()->routeIs($pattern) ? 'menu-open' : '';
    $active = fn (string $pattern) => request()->routeIs($pattern) ? 'active' : '';
@endphp

<aside class="app-sidebar shadow-sm" data-enable-persistence="true">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-mark bg-primary text-white">
                <i class="bi bi-layers-fill"></i>
            </span>
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
        <div class="sidebar-search px-3 pt-3 pb-1">
            <label class="visually-hidden" for="sidebar-search">Search menu</label>
            <div class="input-group input-group-sm sidebar-search-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="sidebar-search" class="form-control" placeholder="Search menu..." autocomplete="off">
            </div>
        </div>

        <nav class="mt-1">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <li class="nav-item" data-nav-label="dashboard">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ $active('dashboard') }}">
                        <i class="nav-icon bi bi-grid-1x2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item {{ $open('masters.*') }}">
                    <a href="#" class="nav-link {{ $active('masters.*') }}">
                        <i class="nav-icon bi bi-sliders"></i>
                        <p>Masters<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="categories"><a href="{{ route('masters.categories.index') }}" class="nav-link {{ $active('masters.categories.*') }}"><i class="nav-icon bi bi-dot"></i><p>Categories</p></a></li>
                        <li class="nav-item" data-nav-label="order formats"><a href="{{ route('masters.formats.index') }}" class="nav-link {{ $active('masters.formats.*') }}"><i class="nav-icon bi bi-dot"></i><p>Order Formats</p></a></li>
                        <li class="nav-item" data-nav-label="customers buyers"><a href="{{ route('masters.buyers.index') }}" class="nav-link {{ $active('masters.buyers.*') }}"><i class="nav-icon bi bi-dot"></i><p>Customers / Buyers</p></a></li>
                        <li class="nav-item" data-nav-label="style master tech pack"><a href="{{ route('masters.styles.index') }}" class="nav-link {{ $active('masters.styles.*') }}"><i class="nav-icon bi bi-dot"></i><p>Style Master &amp; Tech Pack</p></a></li>
                        <li class="nav-item" data-nav-label="bom consumption"><a href="{{ route('masters.bom.index') }}" class="nav-link {{ $active('masters.bom.*') }}"><i class="nav-icon bi bi-dot"></i><p>BOM &amp; Consumption</p></a></li>
                        <li class="nav-item" data-nav-label="item master trims fabric"><a href="{{ route('masters.products.index') }}" class="nav-link {{ $active('masters.products.*') }}"><i class="nav-icon bi bi-dot"></i><p>Item Master (Trims/Fabric)</p></a></li>
                        <li class="nav-item" data-nav-label="suppliers"><a href="{{ route('masters.suppliers.index') }}" class="nav-link {{ $active('masters.suppliers.*') }}"><i class="nav-icon bi bi-dot"></i><p>Suppliers Master</p></a></li>
                        <li class="nav-item" data-nav-label="jobbers job worker"><a href="{{ route('masters.jobbers.index') }}" class="nav-link {{ $active('masters.jobbers.*') }}"><i class="nav-icon bi bi-dot"></i><p>Job Worker / Jobbers</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('sales.*') }}">
                    <a href="#" class="nav-link {{ $active('sales.*') }}">
                        <i class="nav-icon bi bi-bag-check"></i>
                        <p>Sales &amp; Orders<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="enquiry quotations"><a href="{{ route('sales.inquiries.index') }}" class="nav-link {{ $active('sales.inquiries.*') }}"><i class="nav-icon bi bi-dot"></i><p>Enquiry &amp; Quotations</p></a></li>
                        <li class="nav-item" data-nav-label="sales order po"><a href="{{ route('sales.order-confirmations.index') }}" class="nav-link {{ $active('sales.order-confirmations.*') }}"><i class="nav-icon bi bi-dot"></i><p>Sales Order / PO</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('manufacturing.*') }}">
                    <a href="#" class="nav-link {{ $active('manufacturing.*') }}">
                        <i class="nav-icon bi bi-diagram-3"></i>
                        <p>Manufacturing Processes<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="production planning"><a href="{{ route('manufacturing.index') }}" class="nav-link {{ $active('manufacturing.*') }}"><i class="nav-icon bi bi-dot"></i><p>Production Planning</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('inventory.*') || $open('procurement.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('inventory.*') || request()->routeIs('procurement.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-boxes"></i>
                        <p>Inventory &amp; Job Work<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="fabric accessory stock"><a href="{{ route('inventory.index') }}" class="nav-link {{ $active('inventory.*') }}"><i class="nav-icon bi bi-dot"></i><p>Fabric &amp; Accessory Stock</p></a></li>
                        <li class="nav-item" data-nav-label="fabric trims po"><a href="{{ route('procurement.purchase-orders.index') }}" class="nav-link {{ $active('procurement.purchase-orders.*') }}"><i class="nav-icon bi bi-dot"></i><p>Fabric &amp; Trims PO</p></a></li>
                        <li class="nav-item" data-nav-label="goods inward"><a href="{{ route('procurement.inward-entries.index') }}" class="nav-link {{ $active('procurement.inward-entries.*') }}"><i class="nav-icon bi bi-dot"></i><p>Goods Inward</p></a></li>
                        <li class="nav-item" data-nav-label="job work issue receive"><a href="{{ route('masters.jobbers.index') }}" class="nav-link"><i class="nav-icon bi bi-dot"></i><p>Job Work Issue / Receive</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('export.packing.*') || $open('export.documents.*') || $open('finance.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('export.packing.*') || request()->routeIs('export.documents.*') || request()->routeIs('finance.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-box-seam"></i>
                        <p>Packing, Shipment &amp; Billing<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="packing cartons"><a href="{{ route('export.packing.index') }}" class="nav-link {{ $active('export.packing.*') }}"><i class="nav-icon bi bi-dot"></i><p>Packing &amp; Cartons</p></a></li>
                        <li class="nav-item" data-nav-label="export docs invoices"><a href="{{ route('export.documents.index') }}" class="nav-link {{ $active('export.documents.*') }}"><i class="nav-icon bi bi-dot"></i><p>Export Docs &amp; Invoices</p></a></li>
                        <li class="nav-item" data-nav-label="billing payments"><a href="{{ route('finance.purchase-bills.index') }}" class="nav-link {{ $active('finance.*') }}"><i class="nav-icon bi bi-dot"></i><p>Billing &amp; Payments</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('export.ocr.*') || $open('reports.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('export.ocr.*') || request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-stars"></i>
                        <p>Intelligent OCR &amp; Reports<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="ocr document verification"><a href="{{ route('export.ocr.index') }}" class="nav-link {{ $active('export.ocr.*') }}"><i class="nav-icon bi bi-dot"></i><p>OCR Document Verification</p></a></li>
                        <li class="nav-item" data-nav-label="erp reports outstanding"><a href="{{ route('reports.index') }}" class="nav-link {{ $active('reports.*') }}"><i class="nav-icon bi bi-dot"></i><p>ERP Reports &amp; Outstanding</p></a></li>
                    </ul>
                </li>

                <li class="nav-item {{ $open('profile.*') || $open('user-management.*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ request()->routeIs('profile.*') || request()->routeIs('user-management.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-person-circle"></i>
                        <p>Account<i class="nav-arrow bi bi-chevron-right"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item" data-nav-label="profile settings"><a href="{{ route('profile.edit') }}" class="nav-link {{ $active('profile.*') }}"><i class="nav-icon bi bi-dot"></i><p>Profile &amp; Settings</p></a></li>
                        @can('user.view')
                            <li class="nav-item" data-nav-label="users"><a href="{{ route('user-management.users.index') }}" class="nav-link {{ $active('user-management.users.*') }}"><i class="nav-icon bi bi-dot"></i><p>Users</p></a></li>
                        @endcan
                        @can('role.view')
                            <li class="nav-item" data-nav-label="roles"><a href="{{ route('user-management.roles.index') }}" class="nav-link {{ $active('user-management.roles.*') }}"><i class="nav-icon bi bi-dot"></i><p>Roles</p></a></li>
                        @endcan
                        @can('permission.view')
                            <li class="nav-item" data-nav-label="permissions"><a href="{{ route('user-management.permissions.index') }}" class="nav-link {{ $active('user-management.permissions.*') }}"><i class="nav-icon bi bi-dot"></i><p>Permissions</p></a></li>
                        @endcan
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</aside>
