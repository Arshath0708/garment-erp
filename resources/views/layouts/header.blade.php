@php
    $crumbs = \App\Support\Breadcrumbs::trail();
@endphp
<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <ul class="navbar-nav align-items-center flex-grow-1 min-w-0">
            <li class="nav-item">
                <a class="nav-link sidebar-menu-toggle" data-lte-toggle="sidebar" href="#" role="button"
                   aria-label="Toggle sidebar" title="Toggle sidebar">
                    <i class="bi bi-list fs-5"></i>
                </a>
            </li>
            <li class="nav-item flex-grow-1 min-w-0">
                <nav class="erp-breadcrumb" aria-label="Breadcrumb">
                    @foreach ($crumbs as $i => $crumb)
                        @if ($i > 0)
                            <i class="bi bi-chevron-right erp-breadcrumb-sep" aria-hidden="true"></i>
                        @endif
                        @if (! empty($crumb['url']) && $i < count($crumbs) - 1)
                            <a href="{{ $crumb['url'] }}" class="erp-breadcrumb-link">
                                @if ($i === 0)<i class="bi bi-house-door me-1"></i>@endif{{ $crumb['label'] }}
                            </a>
                        @else
                            <span class="erp-breadcrumb-current">
                                @if ($i === 0)<i class="bi bi-house-door me-1"></i>@endif{{ $crumb['label'] }}
                            </span>
                        @endif
                    @endforeach
                </nav>
            </li>
        </ul>

        <ul class="navbar-nav ms-auto align-items-center gap-2">
            <li class="nav-item">
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 px-3 rounded-pill" id="themeToggleBtn" onclick="toggleGarmentTheme()">
                    <i class="bi bi-sun-fill text-warning" id="themeIconSun"></i>
                    <i class="bi bi-moon-stars-fill text-info d-none" id="themeIconMoon"></i>
                    <span id="themeLabelText" class="small fw-bold">Theme</span>
                </button>
            </li>

            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff" class="user-image rounded-circle shadow-sm" alt="User Image" style="width: 32px; height: 32px;">
                    <span class="d-none d-md-inline fw-semibold">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                    <li class="user-header bg-primary text-white p-3 text-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=fff&color=2563eb" class="rounded-circle shadow-sm mb-2" alt="User Image" style="width: 64px; height: 64px;">
                        <p class="mb-0 fw-bold">{{ Auth::user()->name }}</p>
                        <small class="text-white-50">Garment Manufacturing Admin</small>
                    </li>
                    <li class="user-footer p-2 d-flex justify-content-between">
                        <a href="{{ route('profile.edit') }}" class="btn btn-sm btn-outline-secondary">Profile</a>
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger">Sign out</button>
                        </form>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<script>
    function updateThemeUI(theme) {
        const sunIcon = document.getElementById('themeIconSun');
        const moonIcon = document.getElementById('themeIconMoon');
        const labelText = document.getElementById('themeLabelText');
        if (theme === 'dark') {
            sunIcon?.classList.add('d-none');
            moonIcon?.classList.remove('d-none');
            if (labelText) labelText.textContent = 'Dark';
        } else {
            sunIcon?.classList.remove('d-none');
            moonIcon?.classList.add('d-none');
            if (labelText) labelText.textContent = 'Light';
        }
    }

    window.toggleGarmentTheme = function() {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', currentTheme);
        localStorage.setItem('garment_theme', currentTheme);
        updateThemeUI(currentTheme);
    };

    document.addEventListener('DOMContentLoaded', function() {
        const savedTheme = localStorage.getItem('garment_theme') || 'dark';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeUI(savedTheme);
    });
</script>
