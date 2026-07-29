@php
    /**
     * Sidebar.
     *
     * Every entry is permission-gated, and a section header is only rendered
     * when the user can see at least one of its children — so a role with no
     * Accounts permissions never sees an empty "ACCOUNTS" heading.
     *
     * Modules whose screens are not built yet point at "#" and carry a small
     * dot marker, so nobody reports a dead link as a bug.
     */
    $can = fn (string $permission) => auth()->user()?->can($permission);
    $canAny = fn (array $permissions) => collect($permissions)->contains($can);
@endphp

<aside class="app-sidebar shadow-sm" data-bs-theme="light">

    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-mark">GT</span>
            <span class="brand-text">
                Guru Traders
                <small>Export ERP</small>
            </span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-grid-1x2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ============================= MASTERS ============================= --}}
                @if($canAny(['supplier.view', 'buyer.view', 'agent.view', 'product.view', 'category.view', 'po-format.view', 'contract.view', 'markup.view']))
                    <li class="nav-header">Masters</li>

                    @can('supplier.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-truck"></i><p>Supplier</p></a></li>
                    @endcan
                    @can('buyer.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-globe-asia-australia"></i><p>Buyer</p></a></li>
                    @endcan
                    @can('agent.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-person-badge"></i><p>Agent</p></a></li>
                    @endcan
                    {{-- Built. Category first: Product points at it. --}}
                    @can('category.view')
                        <li class="nav-item">
                            <a href="{{ route('masters.categories.index') }}"
                               class="nav-link {{ request()->routeIs('masters.categories.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-tags"></i><p>Category</p>
                            </a>
                        </li>
                    @endcan
                    @can('product.view')
                        <li class="nav-item">
                            <a href="{{ route('masters.products.index') }}"
                               class="nav-link {{ request()->routeIs('masters.products.*') ? 'active' : '' }}">
                                <i class="nav-icon bi bi-box-seam"></i><p>Product</p>
                            </a>
                        </li>
                    @endcan
                    @can('po-format.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-file-earmark-ruled"></i><p>PO Format</p></a></li>
                    @endcan
                    @can('markup.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-percent"></i><p>Markup</p></a></li>
                    @endcan
                @endif

                {{-- =========================== TRANSACTIONS ========================== --}}
                @if($canAny(['inquiry.view', 'quotation.view', 'order-confirmation.view', 'sample-approval.view', 'purchase-order.view', 'material-issue.view', 'inward-entry.view', 'quality-check.view', 'packing.view', 'shipment.view', 'export-document.view']))
                    <li class="nav-header">Transactions</li>

                    @can('inquiry.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-chat-square-text"></i><p>Inquiry</p></a></li>
                    @endcan
                    @can('quotation.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-file-earmark-spreadsheet"></i><p>Quotation</p></a></li>
                    @endcan
                    @can('order-confirmation.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-check2-square"></i><p>Order Confirmation</p></a></li>
                    @endcan
                    @can('sample-approval.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-palette"></i><p>Sample Approval</p></a></li>
                    @endcan
                    @can('purchase-order.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-cart-check"></i><p>Purchase Order</p></a></li>
                    @endcan
                    @can('material-issue.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-box-arrow-up-right"></i><p>Material Issue</p></a></li>
                    @endcan
                    @can('inward-entry.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-box-arrow-in-down"></i><p>Inward Entry</p></a></li>
                    @endcan
                    @can('quality-check.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-clipboard-check"></i><p>Quality Check</p></a></li>
                    @endcan
                    @can('packing.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-boxes"></i><p>Packing</p></a></li>
                    @endcan
                    @can('shipment.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-truck-front"></i><p>Shipment</p></a></li>
                    @endcan
                    @can('export-document.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-files"></i><p>Export Documents</p></a></li>
                    @endcan
                @endif

                {{-- ============================= ACCOUNTS ============================ --}}
                @if($canAny(['purchase-bill.view', 'payment.view', 'foreign-payment.view', 'debit-note.view', 'agent-commission.view', 'outstanding.view']))
                    <li class="nav-header">Accounts</li>

                    @can('purchase-bill.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-receipt"></i><p>Purchase Bills</p></a></li>
                    @endcan
                    @can('payment.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-cash-coin"></i><p>Payments</p></a></li>
                    @endcan
                    @can('foreign-payment.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-currency-exchange"></i><p>Foreign Payments</p></a></li>
                    @endcan
                    @can('debit-note.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-file-earmark-minus"></i><p>Debit Notes</p></a></li>
                    @endcan
                    @can('agent-commission.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-cash-stack"></i><p>Agent Commission</p></a></li>
                    @endcan
                    @can('outstanding.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-hourglass-split"></i><p>Outstanding</p></a></li>
                    @endcan
                @endif

                {{-- ============================== REPORTS ============================ --}}
                @can('report.view')
                    <li class="nav-header">Reports</li>
                    <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-bar-chart-line"></i><p>Reports</p></a></li>
                @endcan

                {{-- =========================== ADMINISTRATION ======================== --}}
                @if($canAny(['user.view', 'role.view', 'permission.view']))
                    <li class="nav-header">Administration</li>

                    <li class="nav-item {{ request()->routeIs('user-management.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people"></i>
                            <p>User Management<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('user.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.users.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.users.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Users</p>
                                    </a>
                                </li>
                            @endcan
                            @can('role.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.roles.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Roles</p>
                                    </a>
                                </li>
                            @endcan
                            @can('permission.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.permissions.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-dot"></i><p>Permissions</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- ============================= SETTINGS ============================ --}}
                @if($canAny(['lookup.view', 'company.view', 'number-series.view', 'activity-log.view']))
                    <li class="nav-header">Settings</li>

                    @can('lookup.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-sliders2"></i><p>Lookups</p></a></li>
                    @endcan
                    @can('company.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-building"></i><p>Company Profile</p></a></li>
                    @endcan
                    @can('number-series.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-123"></i><p>Number Series</p></a></li>
                    @endcan
                    @can('activity-log.view')
                        <li class="nav-item"><a href="#" class="nav-link soon"><i class="nav-icon bi bi-clock-history"></i><p>Activity Logs</p></a></li>
                    @endcan
                @endif

                <li class="nav-header">Account</li>
                <li class="nav-item">
                    <a href="{{ route('profile.edit') }}"
                       class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-person-circle"></i><p>My Profile</p>
                    </a>
                </li>

            </ul>
        </nav>
    </div>
</aside>
