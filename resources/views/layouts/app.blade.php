<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Guru Traders ERP') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')

        <style>
            /* ============================ SIDEBAR — LIGHT ============================ */
            .app-sidebar {
                background: #ffffff;
                border-right: 1px solid #e9edf2;
            }

            /* Brand */
            .app-sidebar .sidebar-brand {
                background: #ffffff;
                border-bottom: 1px solid #eef1f5;
                padding: .9rem 1rem;
            }
            .app-sidebar .brand-link {
                display: flex; align-items: center; gap: .65rem;
                text-decoration: none; padding: 0;
            }
            .app-sidebar .brand-mark {
                width: 38px; height: 38px; flex-shrink: 0;
                display: grid; place-items: center;
                border-radius: 10px;
                background: linear-gradient(135deg, #2563eb, #1d4ed8);
                color: #fff; font-weight: 700; font-size: .95rem; letter-spacing: .5px;
                box-shadow: 0 2px 6px rgba(37, 99, 235, .28);
            }
            .app-sidebar .brand-text {
                display: flex; flex-direction: column; line-height: 1.15;
                font-weight: 600; font-size: .98rem; color: #111827;
            }
            .app-sidebar .brand-text small {
                font-size: .68rem; font-weight: 500; color: #9aa4b2;
                text-transform: uppercase; letter-spacing: .06em;
            }

            /* Section headers */
            .app-sidebar .nav-header {
                color: #9aa4b2;
                font-size: .68rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
                padding: 1.1rem 1.15rem .4rem;
                background: transparent;
            }

            /* Links */
            .app-sidebar .sidebar-menu .nav-link {
                color: #4b5563;
                font-size: .885rem;
                font-weight: 500;
                border-radius: 8px;
                margin: 1px .6rem;
                padding: .5rem .7rem;
                display: flex; align-items: center;
                transition: background-color .12s ease, color .12s ease;
            }
            .app-sidebar .sidebar-menu .nav-link .nav-icon {
                font-size: 1rem;
                width: 1.5rem;
                color: #8b95a5;
                transition: color .12s ease;
            }
            .app-sidebar .sidebar-menu .nav-link:hover {
                background: #f3f6fa;
                color: #111827;
            }
            .app-sidebar .sidebar-menu .nav-link:hover .nav-icon { color: #2563eb; }

            .app-sidebar .sidebar-menu .nav-link.active {
                background: #eff4ff;
                color: #1d4ed8;
                font-weight: 600;
                box-shadow: inset 3px 0 0 #2563eb;
            }
            .app-sidebar .sidebar-menu .nav-link.active .nav-icon { color: #2563eb; }

            /* Treeview children */
            .app-sidebar .nav-treeview .nav-link {
                font-size: .845rem;
                padding-left: 1.9rem;
                margin-left: 1rem;
            }
            .app-sidebar .nav-treeview .nav-link .nav-icon {
                font-size: 1.15rem;
                width: 1rem;
                opacity: .5;
            }

            /* Modules whose screens are not built yet — visible but clearly inert. */
            .app-sidebar .nav-link.soon { color: #a3acb9; cursor: default; }
            .app-sidebar .nav-link.soon .nav-icon { color: #c2c9d4; }
            .app-sidebar .nav-link.soon:hover { background: #f7f9fc; color: #6b7280; }
            .app-sidebar .nav-link.soon > p::after {
                content: "";
                display: inline-block;
                width: 5px; height: 5px;
                border-radius: 50%;
                background: #d6dbe3;
                margin-left: .45rem;
                vertical-align: middle;
            }

            /* Scrollbar */
            .app-sidebar .sidebar-wrapper::-webkit-scrollbar { width: 6px; }
            .app-sidebar .sidebar-wrapper::-webkit-scrollbar-thumb {
                background: #dfe4ea; border-radius: 3px;
            }
            .app-sidebar .sidebar-wrapper::-webkit-scrollbar-thumb:hover { background: #c8cfd8; }

            /* ============================ CONTENT ============================ */
            body { background: #f5f7fa; }
            .app-header { border-bottom: 1px solid #e9edf2; }
            .card { border: 1px solid #e9edf2; }
            .app-content-header h3 { font-weight: 600; color: #111827; }

            /* Permission matrix */
            .matrix-table th { font-weight: 600; font-size: .8125rem; white-space: nowrap; }
            .matrix-table td { padding-top: .4rem; padding-bottom: .4rem; }
            .matrix-table .form-check-input { cursor: pointer; }

            /* ============================ FORMS ============================ */
            /* Long master forms are grouped into labelled sections — see the
               ui.form-section component. A flat 20-field grid is unreadable.
               Note: never write a component tag inside this stylesheet, even in
               a comment — Blade parses it and the layout stops compiling. */
            .form-section { margin-bottom: 1.75rem; }
            .form-section-head {
                display: flex; align-items: flex-start; gap: .65rem;
                padding-bottom: .6rem; margin-bottom: 1.1rem;
                border-bottom: 1px solid #e9edf2;
            }
            .form-section-icon {
                width: 30px; height: 30px; flex-shrink: 0;
                display: grid; place-items: center;
                border-radius: 8px;
                background: #eff4ff; color: #2563eb; font-size: .95rem;
            }
            .form-section-title {
                margin: 0; font-size: .95rem; font-weight: 600; color: #111827;
                line-height: 1.7;
            }
            .form-section-subtitle {
                margin: 0; font-size: .8rem; color: #8b95a5;
            }

            /* Read-only fields the system fills in (auto codes, calculated
               values) look different from fields the user types into. */
            .form-control[readonly] {
                background-color: #f5f7fa;
                color: #6b7280;
                border-style: dashed;
            }

            /* Incentive grid — mirrors the sheet's L..U column layout, so one
               header row instead of the same four labels repeated per scheme. */
            .grid-table { --bs-table-bg: transparent; }
            .grid-table th {
                font-size: .75rem; font-weight: 600; text-transform: uppercase;
                letter-spacing: .04em; color: #8b95a5; white-space: nowrap;
                border-bottom-width: 1px;
            }
            .grid-table td { vertical-align: top; padding: .45rem .4rem; }
            .grid-table td:first-child, .grid-table th:first-child { padding-left: 0; }
            .grid-table .scheme-name { font-weight: 600; color: #374151; padding-top: .5rem; }
            .grid-table .cell-error { font-size: .78rem; color: var(--bs-danger); margin-top: .2rem; }
            .grid-table .na { color: #c2c9d4; padding-top: .5rem; }

            /* One field per line, label on the left. Capped width — a text
               input stretched across a 1900px monitor is harder to read, not
               easier. */
            .form-stack { max-width: 860px; }
            .form-stack .form-line { margin-bottom: 1rem; }
            .form-stack .form-line:last-child { margin-bottom: 0; }
            .form-stack .col-form-label { color: #374151; }
            @media (max-width: 575.98px) {
                .form-stack .col-form-label { padding-bottom: .15rem; }
            }

            /* Save bar stays reachable at the bottom of a long form. */
            .form-actions {
                position: sticky; bottom: 0;
                display: flex; gap: .5rem; align-items: center;
                padding: .85rem 0 .35rem;
                margin-top: .5rem;
                background: linear-gradient(to top, #fff 70%, rgba(255,255,255,0));
                border-top: 1px solid #e9edf2;
            }

            /* Required marker */
            .form-label .req { color: var(--bs-danger); font-weight: 400; }

            /* Role picker cards on the user form */
            .role-option { cursor: pointer; transition: border-color .15s, background-color .15s; }
            .role-option:hover { border-color: var(--bs-primary) !important; }
            .role-option:has(input:checked) {
                border-color: var(--bs-primary) !important;
                background-color: var(--bs-primary-bg-subtle);
            }
        </style>
    </head>
    <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
        <div class="app-wrapper">
            <!-- Header -->
            @include('layouts.header')

            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- App Main -->
            <main class="app-main">
                <!-- App Content Header -->
                @isset($header)
                    <div class="app-content-header">
                        <div class="container-fluid">
                            <div class="row align-items-center">
                                <div class="col-sm-6">
                                    <h3 class="mb-0">{{ $header }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                @endisset

                <!-- App Content -->
                <div class="app-content">
                    <div class="container-fluid">
                        @foreach (['success' => 'check-circle', 'error' => 'exclamation-octagon', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'] as $type => $icon)
                            @if(session($type))
                                <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show d-flex align-items-center" role="alert">
                                    <i class="bi bi-{{ $icon }} me-2"></i>
                                    <div>{{ session($type) }}</div>
                                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif
                        @endforeach

                        {{-- Field-level errors render next to their input. This summary is
                             only useful when several fields failed at once. --}}
                        @if($errors->any() && $errors->count() > 1)
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <div class="fw-semibold mb-1">
                                    <i class="bi bi-exclamation-octagon me-1"></i>Please fix {{ $errors->count() }} problem(s):
                                </div>
                                <ul class="mb-0 ps-4 small">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif

                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Bootstrap tooltips — used by every list screen's action buttons.
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                new bootstrap.Tooltip(el);
            });

            // One confirm handler for every destructive form, instead of an
            // inline onsubmit="return confirm(...)" repeated on each one.
            document.querySelectorAll('form.js-confirm').forEach(function (form) {
                form.addEventListener('submit', function (e) {
                    if (! window.confirm(form.dataset.confirm || 'Are you sure?')) {
                        e.preventDefault();
                    }
                });
            });

            // Modules that have no screen yet.
            document.querySelectorAll('.nav-link.soon').forEach(function (link) {
                link.addEventListener('click', function (e) { e.preventDefault(); });
            });
        });
        </script>

        @stack('scripts')
    </body>
</html>
