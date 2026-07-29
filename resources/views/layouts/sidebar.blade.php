<aside class="app-sidebar bg-body-secondary shadow-sm" data-bs-theme="dark">
    <!-- Sidebar Brand -->
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="brand-link">
            <!-- Brand Logo -->
            <img src="https://adminlte.io/themes/v3/dist/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image opacity-75 shadow">
            <span class="brand-text fw-light">Guru Traders</span>
        </a>
    </div>

    <!-- Sidebar Wrapper -->
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <!-- Sidebar Menu -->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                
                <!-- Dashboard -->
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                
                <li class="nav-header">MASTERS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-building"></i>
                        <p>Company</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-tags"></i>
                        <p>Category</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box-seam"></i>
                        <p>Product</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-person-lines-fill"></i>
                        <p>Buyer (Customer)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-truck"></i>
                        <p>Supplier (Jobber)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-person-badge"></i>
                        <p>Agent</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-aspect-ratio"></i>
                        <p>Unit</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-globe"></i>
                        <p>Country</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>Payment Terms</p>
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-people-fill"></i>
                        <p>
                            User Management
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-person"></i>
                                <p>Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-person-gear"></i>
                                <p>Roles</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="nav-icon bi bi-shield-lock"></i>
                                <p>Permissions</p>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <li class="nav-header">TRANSACTIONS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-question-circle"></i>
                        <p>Inquiry</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-spreadsheet"></i>
                        <p>Quotation</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-check2-circle"></i>
                        <p>Order Confirmation (OC)</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-cart"></i>
                        <p>Purchase Order</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box-arrow-in-right"></i>
                        <p>Inward Entry</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box"></i>
                        <p>Packing</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-send"></i>
                        <p>Shipment</p>
                    </a>
                </li>
                
                <li class="nav-header">EXPORT DOCUMENTS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-journal-text"></i>
                        <p>Item Summary</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-receipt"></i>
                        <p>Commercial Invoice</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-list-check"></i>
                        <p>Packing List</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-medical"></i>
                        <p>Purchase Bills</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-ruled"></i>
                        <p>Export Declaration</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-check"></i>
                        <p>RoDTEP Declaration</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-earmark-text"></i>
                        <p>Bill of Lading</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-journal-bookmark"></i>
                        <p>Shipping Booking</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-ui-checks"></i>
                        <p>Checklist</p>
                    </a>
                </li>
                
                <li class="nav-header">ACCOUNTS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-cash-coin"></i>
                        <p>Payments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-currency-exchange"></i>
                        <p>Foreign Payments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-minus"></i>
                        <p>Debit Notes</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-file-plus"></i>
                        <p>Credit Notes</p>
                    </a>
                </li>
                
                <li class="nav-header">REPORTS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-table"></i>
                        <p>Master Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-bar-chart"></i>
                        <p>Inquiry Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-graph-up-arrow"></i>
                        <p>Quotation Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-pie-chart"></i>
                        <p>OC Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-clipboard-data"></i>
                        <p>Purchase Order Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-box-arrow-in-down-right"></i>
                        <p>Inward Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-boxes"></i>
                        <p>Packing Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-send-check"></i>
                        <p>Shipment Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-airplane"></i>
                        <p>Export Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-cash-stack"></i>
                        <p>Payment Reports</p>
                    </a>
                </li>
                
                <li class="nav-header">SETTINGS</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-sliders"></i>
                        <p>Application Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-building-gear"></i>
                        <p>Company Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-envelope-gear"></i>
                        <p>Email Settings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-database-down"></i>
                        <p>Backup & Restore</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-clock-history"></i>
                        <p>Activity Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-person-circle"></i>
                        <p>Profile</p>
                    </a>
                </li>
                
                <li class="nav-header">HELP</li>
                
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-book"></i>
                        <p>Documentation</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon bi bi-info-circle"></i>
                        <p>About</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
