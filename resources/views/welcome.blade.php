<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Garment ERP — Next-Gen Apparel Manufacturing Suite</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @endif

    <style>
        :root {
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-body: 'Lora', Georgia, serif;
            --bg-dark: #030712;
            --surface-card: rgba(15, 23, 42, 0.75);
            --border-glow: rgba(56, 189, 248, 0.2);
            --cyan-glow: #38bdf8;
            --indigo-glow: #818cf8;
            --emerald-glow: #34d399;
        }

        body {
            font-family: var(--font-body);
            background-color: var(--bg-dark);
            color: #f1f5f9;
            overflow-x: hidden;

            background-image: 
                radial-gradient(circle at 50% 0%, rgba(30, 27, 75, 0.65) 0%, rgba(3, 7, 18, 0.96) 70%),
                radial-gradient(circle at 85% 30%, rgba(56, 189, 248, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 15% 70%, rgba(129, 140, 248, 0.1) 0%, transparent 40%);
            background-attachment: fixed;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: var(--font-heading);
        }

        /* Glassmorphism Navigation */
        .glass-nav {
            background: rgba(3, 7, 18, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        /* Glowing Floating Cards */
        .glass-card {
            background: var(--surface-card);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.4s ease, box-shadow 0.4s ease, opacity 0.6s ease;
        }

        .glass-card:hover {
            border-color: rgba(56, 189, 248, 0.4);
            box-shadow: 0 25px 60px rgba(56, 189, 248, 0.2);
            transform: translateY(-8px) scale(1.01);
        }

        /* Continuous Floating Levitation Animation */
        .floating-element {
            animation: floatingLevitate 5s infinite ease-in-out;
        }

        .floating-element-delay-1 {
            animation: floatingLevitate 6.5s infinite ease-in-out 1s;
        }

        .floating-element-delay-2 {
            animation: floatingLevitate 7s infinite ease-in-out 2s;
        }

        @keyframes floatingLevitate {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
            }
            50% {
                transform: translateY(-12px) rotate(0.5deg);
            }
        }

        /* Floating Background Ambient Particles */
        .bg-floating-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.5;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }

        .orb-1 {
            top: 20%;
            left: 10%;
            width: 280px;
            height: 280px;
            background: rgba(56, 189, 248, 0.15);
        }

        .orb-2 {
            top: 60%;
            right: 12%;
            width: 320px;
            height: 320px;
            background: rgba(129, 140, 248, 0.15);
            animation-delay: -7s;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(40px, -50px) scale(1.2); }
        }

        /* Futuristic Flow Pipeline */
        .flow-container {
            position: relative;
            padding: 40px 0;
        }

        .flow-pipeline-track {
            position: absolute;
            top: 50%;
            left: 5%;
            right: 5%;
            height: 4px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 4px;
            transform: translateY(-50%);
            z-index: 1;
        }

        .flow-pipeline-pulse {
            position: absolute;
            top: 50%;
            left: 0;
            height: 4px;
            width: 30%;
            background: linear-gradient(90deg, transparent, var(--cyan-glow), var(--indigo-glow), transparent);
            border-radius: 4px;
            transform: translateY(-50%);
            z-index: 2;
            animation: flowLaser 3.5s infinite linear;
            box-shadow: 0 0 15px var(--cyan-glow);
        }

        @keyframes flowLaser {
            0% { left: -30%; }
            100% { left: 100%; }
        }

        /* Futuristic Flow Node Cards */
        .flow-node {
            position: relative;
            z-index: 3;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 20px;
            padding: 28px 24px;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
            animation: floatingLevitate 6s ease-in-out infinite;
        }

        .flow-node:nth-child(1) { animation-delay: 0s; }
        .flow-node:nth-child(2) { animation-delay: 1.5s; }
        .flow-node:nth-child(3) { animation-delay: 3s; }
        .flow-node:nth-child(4) { animation-delay: 4.5s; }

        .flow-node:hover {
            border-color: var(--cyan-glow);
            box-shadow: 0 0 35px rgba(56, 189, 248, 0.3);
            transform: scale(1.06) translateY(-10px);
        }

        .flow-node .node-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 18px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            background: rgba(56, 189, 248, 0.1);
            color: var(--cyan-glow);
            border: 1px solid rgba(56, 189, 248, 0.2);
            transition: all 0.4s ease;
        }

        .flow-node:hover .node-icon {
            background: var(--cyan-glow);
            color: #030712;
            box-shadow: 0 0 25px var(--cyan-glow);
        }

        /* Scroll Reveal Floating Class */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(45px);
            transition: opacity 0.8s cubic-bezier(0.16, 1, 0.3, 1), transform 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .reveal-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Hero Text Gradient */
        .text-gradient {
            background: linear-gradient(135deg, #ffffff 0%, #cbd5e1 40%, #38bdf8 80%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .text-gradient-cyan {
            background: linear-gradient(135deg, #38bdf8 0%, #818cf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Premium Buttons */
        .btn-futuristic {
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            padding: 14px 36px;
            font-weight: 600;
            font-family: var(--font-heading);
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
        }

        .btn-futuristic:hover {
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 100%);
            color: #ffffff;
            box-shadow: 0 15px 35px rgba(56, 189, 248, 0.5);
            transform: translateY(-3px);
        }

        .btn-futuristic-outline {
            background: rgba(255, 255, 255, 0.04);
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 50px;
            padding: 14px 36px;
            font-weight: 600;
            font-family: var(--font-heading);
            transition: all 0.3s ease;
        }

        .btn-futuristic-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }

        /* Floating Capsule Widget at Bottom Right */
        .floating-capsule {
            position: fixed;
            bottom: 28px;
            right: 28px;
            z-index: 999;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(56, 189, 248, 0.3);
            border-radius: 50px;
            padding: 10px 22px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6), 0 0 20px rgba(56, 189, 248, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            animation: floatingLevitate 4s ease-in-out infinite;
        }

        .floating-capsule:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #38bdf8;
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 45px rgba(56, 189, 248, 0.4);
        }

        /* Glowing Live Status Pills */
        .live-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.25);
            color: var(--cyan-glow);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.825rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background-color: var(--cyan-glow);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--cyan-glow);
            animation: pulseDot 1.8s infinite ease-in-out;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.7); }
        }
    </style>
</head>
<body>

    <!-- Background Ambient Orbs -->
    <div class="bg-floating-orb orb-1"></div>
    <div class="bg-floating-orb orb-2"></div>

    <!-- Floating Capsule Quick Access Button -->
    <a href="{{ url('/dashboard') }}" class="floating-capsule d-none d-md-flex">
        <span class="live-dot"></span>
        <span class="fw-bold font-heading small">Open ERP Console</span>
        <i class="bi bi-arrow-right-short fs-5"></i>
    </a>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top glass-nav py-3">
        <div class="container px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3" href="#">
                <div class="d-flex align-items-center justify-content-center rounded-3" style="width: 40px; height: 40px; background: linear-gradient(135deg, #0284c7, #6366f1); box-shadow: 0 0 20px rgba(56,189,248,0.4);">
                    <i class="bi bi-layers-fill text-white fs-5"></i>
                </div>
                <span class="fw-bold fs-4 text-white font-heading" style="letter-spacing: -0.5px;">Garment<span class="text-gradient-cyan">ERP</span></span>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav me-auto ms-lg-5 mb-2 mb-lg-0 gap-lg-3">
                    <li class="nav-item"><a class="nav-link text-white-50 hover-white fw-medium" href="#overview">Overview</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 hover-white fw-medium" href="#order-flow">Order Flow</a></li>
                    <li class="nav-item"><a class="nav-link text-white-50 hover-white fw-medium" href="#features">Features</a></li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-futuristic">
                                <i class="bi bi-speedometer2 me-2"></i> Launch Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-futuristic-outline">Sign In</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-futuristic">Create Account</a>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="overview" class="pt-5 mt-5 min-vh-100 d-flex align-items-center relative z-1">
        <div class="container px-lg-4 py-5">
            <div class="row align-items-center g-5">
                
                <div class="col-lg-7 text-center text-lg-start reveal-on-scroll">
                    <div class="live-pill mb-4">
                        <span class="live-dot"></span> Apparel Manufacturing Suite
                    </div>

                    <h1 class="display-3 fw-extrabold mb-4 font-heading" style="line-height: 1.1; letter-spacing: -1.5px;">
                        One Order ID.<br>
                        <span class="text-gradient">From Style Creation To Container Dispatch.</span>
                    </h1>

                    <p class="fs-5 text-white-50 mb-5 max-w-650 fw-normal" style="line-height: 1.6;">
                        A streamlined apparel ERP connecting Buyer POs, Tech Packs, Dynamic BOM Costing, Floor Stage Yields, and Intelligent OCR verification under one continuous digital stream.
                    </p>

                    <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-futuristic btn-lg px-5 py-3">
                                    <i class="bi bi-speedometer2 me-2"></i> Open ERP Application
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-futuristic btn-lg px-5 py-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Access ERP System
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-futuristic-outline btn-lg px-4 py-3">
                                        Register Factory
                                    </a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>

                <!-- Product Preview Floating Widget -->
                <div class="col-lg-5 reveal-on-scroll">
                    <div class="glass-card p-4 floating-element">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-cpu text-info fs-5"></i>
                                <span class="fw-bold text-white font-heading">Order Live Monitor</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2">PO-2026-8841</span>
                        </div>

                        <div class="p-3 rounded-4 mb-4" style="background: rgba(3, 7, 18, 0.6); border: 1px solid rgba(255, 255, 255, 0.08);">
                            <div class="text-white-50 small mb-1">Garment Style Reference</div>
                            <div class="fw-bold text-white fs-6 font-heading">ST-9042 — Men's Casual Woven Shirt</div>
                            <div class="text-info small fw-medium mt-1"><i class="bi bi-bag-check me-1"></i> 12,500 Pcs • Target: 2026-09-15</div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between text-white-50 small mb-2 fw-medium">
                                <span>Stitching Floor Yield</span>
                                <span class="text-white fw-bold">6,200 / 12,500 Pcs (49.6%)</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 10px; background: rgba(255,255,255,0.08);">
                                <div class="progress-bar rounded-pill" style="width: 49.6%; background: linear-gradient(90deg, #38bdf8, #818cf8); box-shadow: 0 0 12px #38bdf8;"></div>
                            </div>
                        </div>

                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                    <div class="text-white-50" style="font-size:0.7rem; font-weight: 600;">CUTTING</div>
                                    <div class="fw-bold text-success font-heading fs-6">100%</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                    <div class="text-white-50" style="font-size:0.7rem; font-weight: 600;">STITCHING</div>
                                    <div class="fw-bold text-warning font-heading fs-6">49.6%</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                                    <div class="text-white-50" style="font-size:0.7rem; font-weight: 600;">QC & PACK</div>
                                    <div class="fw-bold text-info font-heading fs-6">32.8%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Futuristic Order Flow Pipeline Section -->
    <section id="order-flow" class="py-5 my-5 relative z-1">
        <div class="container px-lg-4">
            
            <div class="text-center mb-5 reveal-on-scroll">
                <div class="live-pill mb-3">
                    <span class="live-dot"></span> Futuristic Order Stream
                </div>
                <h2 class="display-5 fw-extrabold text-white font-heading mb-3">The Continuous Garment Lifecycle</h2>
                <p class="text-white-50 fs-5 max-w-600 mx-auto">
                    Data flows seamlessly through every manufacturing node without repetitive entry.
                </p>
            </div>

            <!-- Animated Pipeline Grid -->
            <div class="flow-container reveal-on-scroll">
                <div class="flow-pipeline-track d-none d-lg-block"></div>
                <div class="flow-pipeline-pulse d-none d-lg-block"></div>

                <div class="row g-4">
                    
                    <div class="col-6 col-lg-3">
                        <div class="flow-node">
                            <div class="node-icon">
                                <i class="bi bi-cart-check"></i>
                            </div>
                            <h5 class="fw-bold text-white font-heading mb-2">1. Sales Order</h5>
                            <p class="text-white-50 small mb-0">Buyer PO registration & automated order format mapping.</p>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="flow-node">
                            <div class="node-icon" style="color: #818cf8; border-color: rgba(129, 140, 248, 0.3);">
                                <i class="bi bi-scissors"></i>
                            </div>
                            <h5 class="fw-bold text-white font-heading mb-2">2. Style & BOM</h5>
                            <p class="text-white-50 small mb-0">Tech pack specs, size charts & automated fabric consumption calculation.</p>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="flow-node">
                            <div class="node-icon" style="color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);">
                                <i class="bi bi-gear-wide-connected"></i>
                            </div>
                            <h5 class="fw-bold text-white font-heading mb-2">3. Floor Stages</h5>
                            <p class="text-white-50 small mb-0">Cutting, Printing, Stitching & Finishing yield progress tracking.</p>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="flow-node">
                            <div class="node-icon" style="color: #34d399; border-color: rgba(52, 211, 153, 0.3);">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h5 class="fw-bold text-white font-heading mb-2">4. Container Dispatch</h5>
                            <p class="text-white-50 small mb-0">Quality Control, Packing lists, Export Invoices & Gemini OCR verification.</p>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </section>

    <!-- Features Highlight Section -->
    <section id="features" class="py-5 mb-5 relative z-1">
        <div class="container px-lg-4">
            <div class="row g-4">
                
                <div class="col-md-4 reveal-on-scroll">
                    <div class="glass-card p-4 h-100 floating-element-delay-1">
                        <div class="rounded-3 p-3 mb-3 d-inline-block" style="background: rgba(56, 189, 248, 0.1); color: var(--cyan-glow);">
                            <i class="bi bi-shield-check fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-white font-heading mb-3">Intelligent OCR Desk</h4>
                        <p class="text-white-50 small mb-0">Upload Commercial Invoices or Bills of Lading to automatically compare extracted document quantities against Sales Orders.</p>
                    </div>
                </div>

                <div class="col-md-4 reveal-on-scroll">
                    <div class="glass-card p-4 h-100 floating-element-delay-2">
                        <div class="rounded-3 p-3 mb-3 d-inline-block" style="background: rgba(129, 140, 248, 0.1); color: var(--indigo-glow);">
                            <i class="bi bi-exclamation-triangle fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-white font-heading mb-3">Exception Dashboard</h4>
                        <p class="text-white-50 small mb-0">High-priority alerts for cutting delays, stitching bottlenecks, material shortages, and upcoming export shipment dates.</p>
                    </div>
                </div>

                <div class="col-md-4 reveal-on-scroll">
                    <div class="glass-card p-4 h-100 floating-element">
                        <div class="rounded-3 p-3 mb-3 d-inline-block" style="background: rgba(52, 211, 153, 0.1); color: var(--emerald-glow);">
                            <i class="bi bi-palette fs-3"></i>
                        </div>
                        <h4 class="fw-bold text-white font-heading mb-3">Dual Light & Dark Theme</h4>
                        <p class="text-white-50 small mb-0">Seamlessly toggle between high-contrast dark theme and crisp executive light mode across all screens.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4 border-top border-secondary border-opacity-25 relative z-1" style="background: rgba(3, 7, 18, 0.95);">
        <div class="container px-lg-4 d-flex flex-column flex-md-row justify-content-between align-items-center text-white-50 small">
            <div>&copy; 2026 Garment ERP Suite. Next-Generation Apparel Manufacturing.</div>
            <div class="d-flex gap-4 mt-3 mt-md-0">
                <a href="{{ route('login') }}" class="text-white-50 text-decoration-none hover-white">Sign In</a>
                <a href="{{ route('dashboard') }}" class="text-white-50 text-decoration-none hover-white">ERP Dashboard</a>
            </div>
        </div>
    </footer>

    <!-- Scroll Reveal JavaScript Observer -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                    }
                });
            }, {
                threshold: 0.15
            });

            document.querySelectorAll('.reveal-on-scroll').forEach(el => {
                observer.observe(el);
            });
        });
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <!-- Vite JS -->
    @else
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @endif
</body>
</html>
