@php
    /**
     * Sidebar visibility is driven entirely by permissions.
     *
     * $nav() renders a section only when the user can see at least one of its
     * children, so a role with no Accounts permissions never sees an empty
     * "ACCOUNTS" header. Modules that are not built yet point at "#" and are
     * marked so nobody files a bug about a dead link.
     */
    $canAny = fn (array $permissions) => collect($permissions)->contains(fn ($p) => auth()->user()?->can($p));
@endphp

<aside class="app-sidebar bg-body-secondary shadow-sm" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="Logo"
                 class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light">Guru Traders</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">

                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                {{-- ============================ MASTERS ============================ --}}
                @if($canAny(['category.view', 'product.view', 'buyer.view', 'supplier.view', 'agent.view', 'brand.view', 'warehouse.view']))
                    <li class="nav-header">MASTERS</li>

                    @can('category.view')
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled-module">
                                <i class="nav-icon bi bi-tags"></i><p>Category</p>
                            </a>
                        </li>
                    @endcan
                    @can('product.view')
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled-module">
                                <i class="nav-icon bi bi-box-seam"></i><p>Product</p>
                            </a>
                        </li>
                    @endcan
                    @can('buyer.view')
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled-module">
                                <i class="nav-icon bi bi-person-lines-fill"></i><p>Buyer</p>
                            </a>
                        </li>
                    @endcan
                    @can('supplier.view')
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled-module">
                                <i class="nav-icon bi bi-truck"></i><p>Supplier (Jobber)</p>
                            </a>
                        </li>
                    @endcan
                    @can('agent.view')
                        <li class="nav-item">
                            <a href="#" class="nav-link disabled-module">
                                <i class="nav-icon bi bi-person-badge"></i><p>Agent</p>
                            </a>
                        </li>
                    @endcan
                @endif

                {{-- ========================= GENERAL SETUP ========================= --}}
                @if($canAny(['country.view', 'currency.view', 'port.view', 'unit.view', 'size.view', 'colour.view', 'hsn-code.view', 'incoterm.view', 'payment-term.view']))
                    <li class="nav-header">GENERAL SETUP</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link disabled-module">
                            <i class="nav-icon bi bi-sliders2"></i><p>Lookups</p>
                        </a>
                    </li>
                @endif

                {{-- ========================= TRANSACTIONS ========================== --}}
                @if($canAny(['inquiry.view', 'quotation.view', 'order-confirmation.view', 'purchase-order.view', 'inward-entry.view', 'packing.view', 'shipment.view', 'quality-check.view']))
                    <li class="nav-header">TRANSACTIONS</li>

                    @can('inquiry.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-question-circle"></i><p>Inquiry</p></a></li>
                    @endcan
                    @can('quotation.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-file-earmark-spreadsheet"></i><p>Quotation</p></a></li>
                    @endcan
                    @can('order-confirmation.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-check2-circle"></i><p>Order Confirmation</p></a></li>
                    @endcan
                    @can('sample-approval.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-palette"></i><p>Sample Approval</p></a></li>
                    @endcan
                    @can('purchase-order.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-cart"></i><p>Purchase Order</p></a></li>
                    @endcan
                    @can('material-issue.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-box-arrow-up-right"></i><p>Material Issue</p></a></li>
                    @endcan
                    @can('inward-entry.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-box-arrow-in-right"></i><p>Inward Entry</p></a></li>
                    @endcan
                    @can('quality-check.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-clipboard-check"></i><p>Quality Check</p></a></li>
                    @endcan
                    @can('packing.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-box"></i><p>Packing</p></a></li>
                    @endcan
                    @can('shipment.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-send"></i><p>Shipment</p></a></li>
                    @endcan
                @endif

                {{-- =========================== ACCOUNTS =========================== --}}
                @if($canAny(['purchase-bill.view', 'payment.view', 'foreign-payment.view', 'agent-commission.view', 'debit-note.view', 'credit-note.view', 'outstanding.view']))
                    <li class="nav-header">ACCOUNTS</li>

                    @can('purchase-bill.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-file-earmark-medical"></i><p>Purchase Bills</p></a></li>
                    @endcan
                    @can('payment.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-cash-coin"></i><p>Payments</p></a></li>
                    @endcan
                    @can('foreign-payment.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-currency-exchange"></i><p>Foreign Payments</p></a></li>
                    @endcan
                    @can('agent-commission.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-percent"></i><p>Agent Commission</p></a></li>
                    @endcan
                    @can('debit-note.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-file-minus"></i><p>Debit Notes</p></a></li>
                    @endcan
                    @can('outstanding.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-hourglass-split"></i><p>Outstanding</p></a></li>
                    @endcan
                @endif

                {{-- ============================ REPORTS =========================== --}}
                @can('report.view')
                    <li class="nav-header">REPORTS</li>
                    <li class="nav-item">
                        <a href="#" class="nav-link disabled-module">
                            <i class="nav-icon bi bi-bar-chart"></i><p>Reports</p>
                        </a>
                    </li>
                @endcan

                {{-- ======================= USER MANAGEMENT ======================== --}}
                @if($canAny(['user.view', 'role.view', 'permission.view']))
                    <li class="nav-header">ADMINISTRATION</li>

                    <li class="nav-item {{ request()->routeIs('user-management.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('user-management.*') ? 'active' : '' }}">
                            <i class="nav-icon bi bi-people-fill"></i>
                            <p>User Management<i class="nav-arrow bi bi-chevron-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            @can('user.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.users.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.users.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-person"></i><p>Users</p>
                                    </a>
                                </li>
                            @endcan
                            @can('role.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.roles.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.roles.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-person-gear"></i><p>Roles</p>
                                    </a>
                                </li>
                            @endcan
                            @can('permission.view')
                                <li class="nav-item">
                                    <a href="{{ route('user-management.permissions.index') }}"
                                       class="nav-link {{ request()->routeIs('user-management.permissions.*') ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-shield-lock"></i><p>Permissions</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif

                {{-- ============================ SETTINGS ========================== --}}
                @if($canAny(['setting.view', 'number-series.view', 'activity-log.view', 'backup.view']))
                    <li class="nav-header">SETTINGS</li>

                    @can('setting.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-gear"></i><p>Application Settings</p></a></li>
                    @endcan
                    @can('activity-log.view')
                        <li class="nav-item"><a href="#" class="nav-link disabled-module"><i class="nav-icon bi bi-clock-history"></i><p>Activity Logs</p></a></li>
                    @endcan
                @endif

                <li class="nav-header">ACCOUNT</li>
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
