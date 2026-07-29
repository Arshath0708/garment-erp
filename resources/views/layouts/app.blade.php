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
            /* Modules whose screens are not built yet — visible but clearly inert. */
            .sidebar-menu .disabled-module { opacity: .55; cursor: not-allowed; }
            .sidebar-menu .disabled-module:hover { opacity: .75; }

            /* Permission matrix */
            .matrix-table th { font-weight: 600; font-size: .8125rem; white-space: nowrap; }
            .matrix-table td { padding-top: .4rem; padding-bottom: .4rem; }
            .matrix-table .form-check-input { cursor: pointer; }

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
            document.querySelectorAll('.disabled-module').forEach(function (link) {
                link.addEventListener('click', function (e) { e.preventDefault(); });
            });
        });
        </script>

        @stack('scripts')
    </body>
</html>
