<nav class="app-header navbar navbar-expand bg-body shadow-sm">
    <div class="container-fluid">
        <!-- Start navbar links -->
        <ul class="navbar-nav align-items-center">
            <li class="nav-item">
                <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                    <i class="bi bi-list fs-5"></i>
                </a>
            </li>
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('dashboard') }}" class="nav-link fw-semibold">Dashboard</a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <a href="{{ route('masters.styles.index') }}" class="nav-link"><i class="bi bi-scissors me-1 text-primary"></i> Style Management</a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <a href="{{ route('sales.order-confirmations.index') }}" class="nav-link"><i class="bi bi-cart-check me-1 text-success"></i> Sales Orders</a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <a href="{{ route('manufacturing.index') }}" class="nav-link"><i class="bi bi-gear-wide-connected me-1 text-warning"></i> Manufacturing</a>
            </li>
            <li class="nav-item d-none d-lg-block">
                <a href="{{ route('export.ocr.index') }}" class="nav-link"><i class="bi bi-stars me-1 text-info"></i> OCR Documents</a>
            </li>
        </ul>
        <!-- End navbar links -->

        <!-- Start navbar links (Right) -->
        <ul class="navbar-nav ms-auto align-items-center gap-2">
            <!-- Theme Toggle Button -->
            <li class="nav-item">
                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2 px-3 rounded-pill" id="themeToggleBtn" onclick="toggleGarmentTheme()">
                    <i class="bi bi-sun-fill text-warning" id="themeIconSun"></i>
                    <i class="bi bi-moon-stars-fill text-info d-none" id="themeIconMoon"></i>
                    <span id="themeLabelText" class="small fw-bold">Theme</span>
                </button>
            </li>

            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown user-menu">
                <a href="#" class="nav-link dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=2563eb&color=fff" class="user-image rounded-circle shadow-sm" alt="User Image" style="width: 32px; height: 32px;">
                    <span class="d-none d-md-inline fw-semibold">{{ Auth::user()->name }}</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow">
                    <!-- User image -->
                    <li class="user-header bg-primary text-white p-3 text-center">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=fff&color=2563eb" class="rounded-circle shadow-sm mb-2" alt="User Image" style="width: 64px; height: 64px;">
                        <p class="mb-0 fw-bold">
                            {{ Auth::user()->name }}
                        </p>
                        <small class="text-white-50">Garment Manufacturing Admin</small>
                    </li>
                    <!-- Menu Footer-->
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
            if (labelText) labelText.textContent = '🌙 Dark';
        } else {
            sunIcon?.classList.remove('d-none');
            moonIcon?.classList.add('d-none');
            if (labelText) labelText.textContent = '☀️ Light';
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
