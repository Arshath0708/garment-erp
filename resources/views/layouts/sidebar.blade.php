@php
    $can = fn (string $permission) => auth()->user()?->can($permission);
    $canAny = fn (array $permissions) => collect($permissions)->contains($can);
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
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item" data-nav-label="dashboard home">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ $active('dashboard') }}">
                        <i class="nav-icon bi bi-grid-1x2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ============================= MASTERS ============================= --}}
                @if($canAny(['category.view', 'po-format.view', 'buyer.view', 'style-costing.view', 'product.view', 'supplier.view', 'jobber.view', 'agent.view', 'fob-value.view', 'markup.view']))
                    <li class="nav-header">Masters</li>

                    @can('category.view')
                        <li class="nav-item" data-nav-label="categories">
                            <a href="{{ route('masters.categories.index') }}" class="nav-link {{ $active('masters.categories.*') }}">
                                <i class="nav-icon bi bi-tags"></i><p>Categories</p>
                            </a>
                        </li>
                    @endcan

                    @can('po-format.view')
                        <li class="nav-item" data-nav-label="order formats">
                            <a href="{{ route('masters.formats.index') }}" class="nav-link {{ $active('masters.formats.*') }}">
                                <i class="nav-icon bi bi-file-earmark-ruled"></i><p>Order Formats</p>
                            </a>
                        </li>
                    @endcan

                    @can('buyer.view')
                        <li class="nav-item" data-nav-label="customers buyers">
                            <a href="{{ route('masters.buyers.index') }}" class="nav-link {{ $active('masters.buyers.*') }}">
                                <i class="nav-icon bi bi-globe-asia-australia"></i><p>Buyers</p>
                            </a>
                        </li>
                    @endcan

                    @if($can('style-costing.view') || $can('product.view'))
                        <li class="nav-item" data-nav-label="style master tech pack">
                            <a href="{{ route('masters.styles.index') }}" class="nav-link {{ $active('masters.styles.*') }}">
                                <i class="nav-icon bi bi-scissors"></i><p>Style Master &amp; Tech Pack</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="bom consumption">
                            <a href="{{ route('masters.bom.index') }}" class="nav-link {{ $active('masters.bom.*') }}">
                                <i class="nav-icon bi bi-calculator"></i><p>BOM &amp; Consumption</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="style costing cost sheet">
                            <a href="{{ route('style-costings.index') }}" class="nav-link {{ $active('style-costings.*') }}">
                                <i class="nav-icon bi bi-currency-rupee"></i><p>Style Costing</p>
                            </a>
                        </li>
                    @endif

                    @can('product.view')
                        <li class="nav-item" data-nav-label="item master trims fabric products">
                            <a href="{{ route('masters.products.index') }}" class="nav-link {{ $active('masters.products.*') }}">
                                <i class="nav-icon bi bi-box-seam"></i><p>Products / Item Master</p>
                            </a>
                        </li>
                    @endcan

                    @can('supplier.view')
                        <li class="nav-item" data-nav-label="suppliers">
                            <a href="{{ route('masters.suppliers.index') }}" class="nav-link {{ $active('masters.suppliers.*') }}">
                                <i class="nav-icon bi bi-truck"></i><p>Suppliers</p>
                            </a>
                        </li>
                    @endcan

                    @if($can('jobber.view') || $can('supplier.view'))
                        <li class="nav-item" data-nav-label="jobbers job worker">
                            <a href="{{ route('masters.jobbers.index') }}" class="nav-link {{ $active('masters.jobbers.*') }}">
                                <i class="nav-icon bi bi-tools"></i><p>Jobbers</p>
                            </a>
                        </li>
                    @endif

                    @can('agent.view')
                        <li class="nav-item" data-nav-label="agents">
                            <a href="{{ route('masters.agents.index') }}" class="nav-link {{ $active('masters.agents.*') }}">
                                <i class="nav-icon bi bi-person-badge"></i><p>Agents</p>
                            </a>
                        </li>
                    @endcan

                    @can('fob-value.view')
                        <li class="nav-item" data-nav-label="fob values">
                            <a href="{{ route('masters.fob-values.index') }}" class="nav-link {{ $active('masters.fob-values.*') }}">
                                <i class="nav-icon bi bi-currency-dollar"></i><p>FOB Values</p>
                            </a>
                        </li>
                    @endcan

                    @can('markup.view')
                        <li class="nav-item" data-nav-label="markup">
                            <a href="{{ route('masters.markups.index') }}" class="nav-link {{ $active('masters.markups.*') }}">
                                <i class="nav-icon bi bi-percent"></i><p>Markup</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ============================== SALES ============================== --}}
                @if($canAny(['inquiry.view', 'order-confirmation.view']))
                    <li class="nav-header">Sales</li>

                    @can('inquiry.view')
                        <li class="nav-item" data-nav-label="inquiries enquiry quotations">
                            <a href="{{ route('sales.inquiries.index') }}" class="nav-link {{ $active('sales.inquiries.*') }}">
                                <i class="nav-icon bi bi-chat-square-text"></i><p>Inquiries</p>
                            </a>
                        </li>
                    @endcan

                    @can('order-confirmation.view')
                        <li class="nav-item" data-nav-label="order confirmations sales order po">
                            <a href="{{ route('sales.order-confirmations.index') }}" class="nav-link {{ $active('sales.order-confirmations.*') }}">
                                <i class="nav-icon bi bi-check2-square"></i><p>Order Confirmations</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- =========================== PROCUREMENT =========================== --}}
                @if($canAny(['purchase-order.view', 'inward-entry.view']))
                    <li class="nav-header">Procurement</li>

                    @can('purchase-order.view')
                        <li class="nav-item" data-nav-label="purchase orders fabric trims po">
                            <a href="{{ route('procurement.purchase-orders.index') }}" class="nav-link {{ $active('procurement.purchase-orders.*') }}">
                                <i class="nav-icon bi bi-cart-check"></i><p>Purchase Orders</p>
                            </a>
                        </li>
                    @endcan

                    @can('inward-entry.view')
                        <li class="nav-item" data-nav-label="goods inward">
                            <a href="{{ route('procurement.inward-entries.index') }}" class="nav-link {{ $active('procurement.inward-entries.*') }}">
                                <i class="nav-icon bi bi-box-arrow-in-down"></i><p>Goods Inward</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ==================== MANUFACTURING PROCESSES ==================== --}}
                @if($canAny(['work-order.view', 'job-work.view', 'product.view']))
                    <li class="nav-header">Manufacturing</li>

                    @if($can('work-order.view') || $can('product.view'))
                        <li class="nav-item" data-nav-label="work orders">
                            <a href="{{ route('work-orders.index') }}" class="nav-link {{ $active('work-orders.*') }}">
                                <i class="nav-icon bi bi-clipboard-check"></i><p>Work Orders</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="time and action tna">
                            <a href="{{ route('time-and-action.index') }}" class="nav-link {{ $active('time-and-action.*') }}">
                                <i class="nav-icon bi bi-calendar-week"></i><p>Time &amp; Action</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="production planning">
                            <a href="{{ route('manufacturing.index') }}" class="nav-link {{ $active('manufacturing.index') || $active('manufacturing.create') || $active('manufacturing.show') || $active('manufacturing.edit') }}">
                                <i class="nav-icon bi bi-diagram-3"></i><p>Production Planning</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="qc capa defect">
                            <a href="{{ route('manufacturing.capa.index') }}" class="nav-link {{ $active('manufacturing.capa.*') }}">
                                <i class="nav-icon bi bi-clipboard2-check"></i><p>QC CAPA</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="line efficiency sewing">
                            <a href="{{ route('production-lines.index') }}" class="nav-link {{ $active('production-lines.*') }}">
                                <i class="nav-icon bi bi-speedometer2"></i><p>Line Efficiency</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="phone scan barcode floor">
                            <a href="{{ route('floor.scan') }}" class="nav-link {{ $active('floor.*') }}">
                                <i class="nav-icon bi bi-upc-scan"></i><p>Phone Scan</p>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ====================== INVENTORY & JOB WORK ====================== --}}
                @if($canAny(['warehouse.view', 'job-work.view', 'product.view']))
                    <li class="nav-header">Inventory &amp; Job Work</li>

                    <li class="nav-item" data-nav-label="fabric accessory stock inventory">
                        <a href="{{ route('inventory.index') }}" class="nav-link {{ $active('inventory.index') || $active('inventory.lots') }}">
                            <i class="nav-icon bi bi-boxes"></i><p>Stock Inventory</p>
                        </a>
                    </li>

                    @can('warehouse.view')
                        <li class="nav-item" data-nav-label="godowns warehouses lots">
                            <a href="{{ route('inventory.warehouses.index') }}" class="nav-link {{ $active('inventory.warehouses.*') }}">
                                <i class="nav-icon bi bi-building"></i><p>Godowns / Warehouses</p>
                            </a>
                        </li>
                    @endcan

                    @if($can('job-work.view') || $can('product.view'))
                        <li class="nav-item" data-nav-label="job work issue receive">
                            <a href="{{ route('job-work.index') }}" class="nav-link {{ $active('job-work.*') }}">
                                <i class="nav-icon bi bi-arrow-left-right"></i><p>Job Work</p>
                            </a>
                        </li>
                    @endif
                @endif

                {{-- ============================== EXPORT ============================= --}}
                @if($canAny(['packing.view', 'export-document.view']))
                    <li class="nav-header">Export</li>

                    @can('packing.view')
                        <li class="nav-item" data-nav-label="packing cartons">
                            <a href="{{ route('export.packing.index') }}" class="nav-link {{ $active('export.packing.*') }}">
                                <i class="nav-icon bi bi-boxes"></i><p>Packing</p>
                            </a>
                        </li>
                    @endcan

                    @can('export-document.view')
                        <li class="nav-item" data-nav-label="export documents">
                            <a href="{{ route('export.documents.index') }}" class="nav-link {{ $active('export.documents.*') }}">
                                <i class="nav-icon bi bi-files"></i><p>Export Documents</p>
                            </a>
                        </li>
                        <li class="nav-item" data-nav-label="ocr document verification">
                            <a href="{{ route('export.ocr.index') }}" class="nav-link {{ $active('export.ocr.*') }}">
                                <i class="nav-icon bi bi-stars"></i><p>Document OCR</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ============================= FINANCE ============================= --}}
                @if($canAny(['purchase-bill.view', 'debit-note.view', 'payment.view', 'foreign-payment.view', 'agent-commission.view', 'tally.view', 'whatsapp.view']))
                    <li class="nav-header">Finance</li>

                    @can('purchase-bill.view')
                        <li class="nav-item" data-nav-label="purchase bills">
                            <a href="{{ route('finance.purchase-bills.index') }}" class="nav-link {{ $active('finance.purchase-bills.*') }}">
                                <i class="nav-icon bi bi-receipt"></i><p>Purchase Bills</p>
                            </a>
                        </li>
                    @endcan

                    @can('debit-note.view')
                        <li class="nav-item" data-nav-label="debit notes">
                            <a href="{{ route('finance.debit-notes.index') }}" class="nav-link {{ $active('finance.debit-notes.*') }}">
                                <i class="nav-icon bi bi-file-earmark-minus"></i><p>Debit Notes</p>
                            </a>
                        </li>
                    @endcan

                    @can('payment.view')
                        <li class="nav-item" data-nav-label="supplier payments">
                            <a href="{{ route('finance.supplier-payments.index') }}" class="nav-link {{ $active('finance.supplier-payments.*') }}">
                                <i class="nav-icon bi bi-cash-coin"></i><p>Supplier Payments</p>
                            </a>
                        </li>
                    @endcan

                    @can('foreign-payment.view')
                        <li class="nav-item" data-nav-label="buyer receipts">
                            <a href="{{ route('finance.buyer-receipts.index') }}" class="nav-link {{ $active('finance.buyer-receipts.*') }}">
                                <i class="nav-icon bi bi-currency-exchange"></i><p>Buyer Receipts</p>
                            </a>
                        </li>
                    @endcan

                    @can('agent-commission.view')
                        <li class="nav-item" data-nav-label="agent commission">
                            <a href="{{ route('finance.agent-commission.index') }}" class="nav-link {{ $active('finance.agent-commission.*') }}">
                                <i class="nav-icon bi bi-cash-stack"></i><p>Agent Commission</p>
                            </a>
                        </li>
                    @endcan

                    @can('tally.view')
                        <li class="nav-item" data-nav-label="tally gst posting">
                            <a href="{{ route('finance.tally.settings') }}" class="nav-link {{ $active('finance.tally*') }}">
                                <i class="nav-icon bi bi-hdd-network"></i><p>Tally</p>
                            </a>
                        </li>
                    @endcan

                    @can('whatsapp.view')
                        <li class="nav-item" data-nav-label="whatsapp alerts">
                            <a href="{{ route('whatsapp.settings') }}" class="nav-link {{ $active('whatsapp.*') }}">
                                <i class="nav-icon bi bi-whatsapp"></i><p>WhatsApp</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ============================= REPORTS ============================= --}}
                @if($canAny(['outstanding.view', 'report.view']))
                    <li class="nav-header">Reports</li>

                    @can('outstanding.view')
                        <li class="nav-item" data-nav-label="outstanding report">
                            <a href="{{ route('reports.outstanding.index') }}" class="nav-link {{ $active('reports.outstanding.*') }}">
                                <i class="nav-icon bi bi-hourglass-split"></i><p>Outstanding</p>
                            </a>
                        </li>
                    @endcan

                    @can('report.view')
                        <li class="nav-item" data-nav-label="erp reports factory board">
                            <a href="{{ route('reports.index') }}" class="nav-link {{ $active('reports.*') && !$active('reports.outstanding.*') }}">
                                <i class="nav-icon bi bi-bar-chart-line"></i><p>Reports</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ========================== ADMINISTRATION ========================= --}}
                @if($canAny(['user.view', 'role.view', 'permission.view', 'company-profile.view']))
                    <li class="nav-header">Administration</li>

                    @can('company-profile.view')
                        <li class="nav-item" data-nav-label="company profile">
                            <a href="{{ route('user-management.company-profile.edit') }}" class="nav-link {{ $active('user-management.company-profile.*') }}">
                                <i class="nav-icon bi bi-buildings"></i><p>Company Profile</p>
                            </a>
                        </li>
                    @endcan

                    <li class="nav-item {{ request()->routeIs('user-management.*') && !request()->routeIs('user-management.company-profile.*') ? 'menu-open' : '' }}" data-nav-label="user management users roles permissions">
                        <a href="#" class="nav-link {{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people"></i>
                            <p>User Management<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('user.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.users.index') }}" class="nav-link {{ $active('user-management.users.*') }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Users</p>
                                    </a>
                                </li>
                            @endcan
                            @can('role.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.roles.index') }}" class="nav-link {{ $active('user-management.roles.*') }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Roles</p>
                                    </a>
                                </li>
                            @endcan
                            @can('permission.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.permissions.index') }}" class="nav-link {{ $active('user-management.permissions.*') }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Permissions</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</aside>
