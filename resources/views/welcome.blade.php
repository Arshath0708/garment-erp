<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Garment ERP — Production & Order Management Suite</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="garment-erp-theme" x-data="{ demoModalOpen: false }">

    <!-- Public Navigation Header -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: rgba(11, 15, 25, 0.9); backdrop-filter: blur(16px); border-bottom: 1px solid rgba(255,255,255,0.08);">
        <div class="container px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="#">
                <div class="bg-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                    <i class="bi bi-layers-fill text-white fs-5"></i>
                </div>
                <span class="fw-bold fs-5 text-white" style="font-family: 'Outfit', sans-serif;">Garment ERP</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0 gap-lg-2">
                    <li class="nav-item"><a class="nav-link text-white-50" href="#overview">Overview</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#workflow">Order Journey</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50" href="#value-prop">Value Proposition</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-erp-primary"><i class="bi bi-speedometer2 me-1"></i> Open ERP Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-light rounded-pill px-4">Sign In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-primary rounded-pill px-4 fw-bold">Create Account</a>
                            @endif
                        @endauth
                    @endif
                    <button class="btn btn-erp-secondary" @click="demoModalOpen = true">Request Demo</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header id="overview" class="garment-hero">
        <div class="hero-glow-blob blob-1"></div>
        <div class="hero-glow-blob blob-2"></div>
        <div class="container relative z-1">
            <div class="row align-items-center g-5 min-vh-75">
                <div class="col-lg-7 text-center text-lg-start">
                    <div class="erp-badge erp-badge-primary mb-3">
                        <i class="bi bi-cpu-fill"></i> GARMENT MANUFACTURING SUITE
                    </div>
                    <h1 class="display-4 fw-extrabold text-white mb-3" style="font-family: 'Outfit', sans-serif; line-height: 1.15;">
                        One Single Order ID.<br>
                        <span style="background: linear-gradient(135deg, #60a5fa 0%, #38bdf8 50%, #818cf8 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            From Style Creation to Container Dispatch.
                        </span>
                    </h1>
                    <p class="section-desc mb-4">
                        Garment ERP is a specialized apparel manufacturing platform designed to track the complete order lifecycle. Connect buyer POs, tech packs, dynamic BOM costing, floor production stages, multi-location inventory, and document OCR verification under one unified system.
                    </p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start mb-4">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-erp-primary btn-lg">
                                    <i class="bi bi-speedometer2 me-1"></i> Go to ERP Application
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-erp-primary btn-lg">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to ERP
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-erp-secondary btn-lg">
                                        Create Free Account
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>

                <!-- Product Preview Visual Card -->
                <div class="col-lg-5">
                    <div class="erp-card p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="fw-bold text-white small"><i class="bi bi-layers-fill text-primary me-1"></i> Order Progress Console</div>
                            <span class="erp-badge erp-badge-success">PO-2026-8841</span>
                        </div>
                        <div class="p-3 rounded-3 mb-3" style="background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.08);">
                            <div class="text-muted small">Garment Style Reference</div>
                            <div class="fw-bold text-white fs-6">ST-9042 — Men's Casual Woven Shirt</div>
                            <div class="text-info small">12,500 Pcs • Target: 2026-09-15</div>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between text-muted small mb-1">
                                <span>Stitching Stage Yield</span>
                                <span>6,200 / 12,500 Pcs (49.6%)</span>
                            </div>
                            <div class="progress" style="height: 8px; background: rgba(255,255,255,0.1);">
                                <div class="progress-bar bg-primary" style="width: 49.6%;"></div>
                            </div>
                        </div>

                        <div class="row g-2 text-center text-white-50 small">
                            <div class="col-4 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                <div class="text-muted" style="font-size:0.7rem;">CUTTING</div>
                                <div class="fw-bold text-success">100%</div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                <div class="text-muted" style="font-size:0.7rem;">STITCHING</div>
                                <div class="fw-bold text-warning">49.6%</div>
                            </div>
                            <div class="col-4 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                <div class="text-muted" style="font-size:0.7rem;">QC & PACK</div>
                                <div class="fw-bold text-info">32.8%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Workflow Section -->
    <section id="workflow" class="py-5" style="background: rgba(15, 23, 42, 0.6);">
        <div class="container px-lg-4">
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="section-tag">Connected Order Journey</span>
                <h2 class="section-title">The Complete Apparel Manufacturing Workflow</h2>
                <p class="section-desc mx-auto">
                    Instead of disconnected departmental silos, Garment ERP tracks every stage under one central order ID.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="erp-card p-4 h-100">
                        <div class="text-primary fs-3 mb-2"><i class="bi bi-scissors"></i></div>
                        <h5 class="fw-bold text-white mb-2">1. Style & Tech Pack</h5>
                        <p class="text-muted small mb-0">Define style numbers, fabric composition, colorways, measurement charts, and construction specs.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="erp-card p-4 h-100">
                        <div class="text-info fs-3 mb-2"><i class="bi bi-calculator"></i></div>
                        <h5 class="fw-bold text-white mb-2">2. Dynamic BOM & Costing</h5>
                        <p class="text-muted small mb-0">Automate material requirement calculations ($\text{Qty} \times \text{Consumption}$) and calculate target gross margins.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="erp-card p-4 h-100">
                        <div class="text-warning fs-3 mb-2"><i class="bi bi-gear-wide-connected"></i></div>
                        <h5 class="fw-bold text-white mb-2">3. Floor Manufacturing</h5>
                        <p class="text-muted small mb-0">Monitor live output across Cutting, Stitching, Finishing, Quality Check, Packing, and Container Dispatch.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Value Proposition Section -->
    <section id="value-prop" class="py-5">
        <div class="container px-lg-4">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <span class="section-tag">Exception-Based Management</span>
                    <h2 class="section-title">Designed for Fast Factory Decision-Making</h2>
                    <p class="section-desc mb-4">
                        Stop searching through dozens of screens. Garment ERP highlights delays, material shortages, and quantity mismatches so floor managers can take instant corrective action.
                    </p>
                    <ul class="list-unstyled text-white-50 small mb-4">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Single Buyer Sales Order ID tracking across all departments</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Multi-location warehouse inventory (Tirupur, Bangalore, Mumbai, Delhi, Dubai)</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i> Document OCR verification comparing invoice quantities against ERP orders</li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <div class="erp-card p-4">
                        <h5 class="fw-bold text-white mb-3"><i class="bi bi-shield-check text-success me-2"></i> Production-Ready Enterprise Platform</h5>
                        <p class="text-muted small mb-4">Ready to test the system? Log in using your credentials or request a live demonstration.</p>
                        <div class="d-flex gap-3">
                            <a href="{{ route('login') }}" class="btn btn-erp-primary w-100">Sign In Now</a>
                            <button class="btn btn-erp-secondary w-100" @click="demoModalOpen = true">Request Demo</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top border-secondary border-opacity-25" style="background: rgba(11, 15, 25, 0.95);">
        <div class="container px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center text-muted small">
            <div>&copy; 2026 Garment ERP. All rights reserved.</div>
            <div class="d-flex gap-3 mt-2 mt-md-0">
                <a href="{{ route('login') }}" class="text-white-50 text-decoration-none">Sign In</a>
                <a href="#" class="text-white-50 text-decoration-none" @click.prevent="demoModalOpen = true">Request Demo</a>
            </div>
        </div>
    </footer>

    <!-- Demo Request Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1" :class="{ 'show d-block': demoModalOpen }" style="background: rgba(0,0,0,0.7);" x-show="demoModalOpen" x-transition>
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content erp-card text-white p-4" style="background: #0f172a; border: 1px solid rgba(59, 130, 246, 0.3);">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Request Garment ERP Demo</h5>
                    <button type="button" class="btn-close btn-close-white" @click="demoModalOpen = false"></button>
                </div>
                <form @submit.prevent="demoModalOpen = false; alert('Thank you! A specialist will contact you shortly.');">
                    <div class="mb-3">
                        <label class="text-muted small">Full Name</label>
                        <input type="text" class="erp-input" placeholder="e.g. Rahul Sharma" required>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Company / Garment Unit</label>
                        <input type="text" class="erp-input" placeholder="e.g. Apex Apparel Exports" required>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted small">Phone / WhatsApp</label>
                        <input type="tel" class="erp-input" placeholder="+91 98765 43210" required>
                    </div>
                    <button type="submit" class="btn btn-erp-primary w-100">Submit Request</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
