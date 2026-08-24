<aside class="app-sidebar shadow-sm" data-enable-persistence="true">

    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link d-flex align-items-center gap-2 text-decoration-none">
            <span class="brand-mark bg-primary text-white rounded p-1 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                <i class="bi bi-layers-fill fs-5"></i>
            </span>
            <span class="brand-text d-flex flex-column">
                <span class="brand-text-title fs-6 lh-1">Garment ERP</span>
                <small class="brand-text-subtitle" style="font-size: 0.65rem;">Apparel Manufacturing</small>
            </span>
        </a>

        <button type="button" class="sidebar-toggle btn btn-sm text-secondary border-0" data-lte-toggle="sidebar"
                aria-label="Collapse sidebar" title="Collapse sidebar">
            <i class="bi bi-chevron-double-left"></i>
        </button>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <!-- 1. DASHBOARD -->
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-grid-1x2-fill"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- 2. MASTERS -->
                <li class="nav-header">MASTERS</li>
                <li class="nav-item">
                    <a href="{{ route('masters.categories.index') }}" class="nav-link {{ request()->routeIs('masters.categories.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-tags-fill"></i>
                        <p>Categories</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.formats.index') }}" class="nav-link {{ request()->routeIs('masters.formats.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-file-earmark-ruled"></i>
                        <p>Order Formats</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.buyers.index') }}" class="nav-link {{ request()->routeIs('masters.buyers.*') ? 'active' : '' }}">


                        <i class="nav-icon bi bi-person-badge"></i>
                        <p>Customers / Buyers</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.styles.index') }}" class="nav-link {{ request()->routeIs('masters.styles.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-scissors"></i>
                        <p>Style Master & Tech Pack</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.bom.index') }}" class="nav-link {{ request()->routeIs('masters.bom.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-calculator"></i>
                        <p>BOM & Consumption</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.products.index') }}" class="nav-link {{ request()->routeIs('masters.products.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-box-seam"></i>
                        <p>Item Master (Trims/Fabric)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.suppliers.index') }}" class="nav-link {{ request()->routeIs('masters.suppliers.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-truck"></i>
                        <p>Suppliers Master</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.jobbers.index') }}" class="nav-link {{ request()->routeIs('masters.jobbers.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-building-gear"></i>
                        <p>Job Worker / Jobbers</p>
                    </a>
                </li>

                <!-- 3. SALES & ORDERS -->
                <li class="nav-header">SALES & ORDERS</li>
                <li class="nav-item">
                    <a href="{{ route('sales.inquiries.index') }}" class="nav-link {{ request()->routeIs('sales.inquiries.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-chat-quote"></i>
                        <p>Enquiry & Quotations</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('sales.order-confirmations.index') }}" class="nav-link {{ request()->routeIs('sales.order-confirmations.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-file-earmark-check"></i>
                        <p>Sales Order / PO</p>
                    </a>
                </li>

                <!-- 4. PLANNING & PRODUCTION -->
                <li class="nav-header">MANUFACTURING PROCESSES</li>
                <li class="nav-item">

                    <a href="{{ route('manufacturing.index') }}" class="nav-link {{ request()->routeIs('manufacturing.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-diagram-3-fill"></i>
                        <p>Production Planning</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manufacturing.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-scissors text-warning"></i>
                        <p>Cutting Process</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manufacturing.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-printer text-info"></i>
                        <p>Printing & Embroidery</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manufacturing.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-gear-wide-connected text-primary"></i>
                        <p>Stitching Process</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('manufacturing.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-shield-check text-success"></i>
                        <p>Finishing & Quality Control</p>
                    </a>
                </li>

                <!-- 5. INVENTORY & JOB WORK -->
                <li class="nav-header">INVENTORY & JOB WORK</li>
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-boxes"></i>
                        <p>Fabric &amp; Accessory Stock</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('procurement.purchase-orders.index') }}" class="nav-link {{ request()->routeIs('procurement.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-cart-plus"></i>
                        <p>Fabric & Trims PO / Inward</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('masters.jobbers.index') }}" class="nav-link">
                        <i class="nav-icon bi bi-arrow-left-right text-purple"></i>
                        <p>Job Work Issue / Receive</p>
                    </a>
                </li>

                <!-- 6. PACKING, SHIPMENT & BILLING -->
                <li class="nav-header">PACKING, SHIPMENT & BILLING</li>
                <li class="nav-item">
                    <a href="{{ route('export.packing.index') }}" class="nav-link {{ request()->routeIs('export.packing.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-box-seam"></i>
                        <p>Packing & Cartons</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('export.documents.index') }}" class="nav-link {{ request()->routeIs('export.documents.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-file-earmark-pdf"></i>
                        <p>Export Docs & Invoices</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('finance.purchase-bills.index') }}" class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>Billing & Payments</p>
                    </a>
                </li>

                <!-- 7. OCR & VERIFICATION -->
                <li class="nav-header">INTELLIGENT OCR & REPORTS</li>
                <li class="nav-item">
                    <a href="{{ route('export.ocr.index') }}" class="nav-link {{ request()->routeIs('export.ocr.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-stars text-warning"></i>
                        <p>OCR Document Verification</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-bar-chart-line-fill"></i>
                        <p>ERP Reports & Outstanding</p>
                    </a>
                </li>

                <!-- 8. ACCOUNT & SETTINGS -->
                <li class="nav-header">ACCOUNT</li>
                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}" class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-person-circle"></i>
                        <p>Profile & Settings</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
