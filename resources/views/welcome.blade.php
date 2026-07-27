<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guru Traders ERP</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #06b6d4;
            --bg-dark: #0f172a;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            color: #f8fafc;
            overflow-x: hidden;
        }
        h1, h2, h3, .brand-logo {
            font-family: 'Outfit', sans-serif;
        }
        
        /* Navbar Glassmorphism */
        .navbar-glass {
            background: rgba(15, 23, 42, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 80px; /* Offset for navbar */
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, rgba(15, 23, 42, 0) 50%);
            animation: pulse-glow 15s infinite alternate;
            z-index: -1;
        }
        @keyframes pulse-glow {
            0% { transform: scale(1); opacity: 0.8; }
            100% { transform: scale(1.1); opacity: 1; }
        }
        
        .hero-title {
            font-size: 4.5rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            background: linear-gradient(to right, #ffffff, #94a3b8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 1s ease forwards 0.2s;
        }
        .hero-subtitle {
            font-size: 1.25rem;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 1s ease forwards 0.4s;
        }
        
        /* Buttons */
        .btn-glow {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            color: white;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.4);
            transition: all 0.3s ease;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 1s ease forwards 0.6s;
        }
        .btn-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(6, 182, 212, 0.5);
            color: white;
        }
        .btn-outline-glow {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 1s ease forwards 0.8s;
        }
        .btn-outline-glow:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
            color: white;
            transform: translateY(-3px);
        }

        /* Floating Element Graphic */
        .hero-visual {
            position: relative;
            z-index: 2;
            opacity: 0;
            animation: fadeUp 1s ease forwards 0.8s;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 2rem;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            transform: perspective(1000px) rotateY(-15deg) rotateX(10deg);
            transition: transform 0.5s ease;
        }
        .glass-card:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
        }
        .stats-badge {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(56, 189, 248, 0.2);
            opacity: 0;
            animation: fadeUp 1s ease forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Abstract shapes */
        .shape-1, .shape-2 {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
        }
        .shape-1 {
            width: 400px;
            height: 400px;
            background: rgba(37, 99, 235, 0.3);
            top: -100px;
            right: -100px;
        }
        .shape-2 {
            width: 300px;
            height: 300px;
            background: rgba(168, 85, 247, 0.2);
            bottom: -50px;
            left: -50px;
        }
        
        @media (max-width: 991px) {
            .hero-title { font-size: 3rem; }
            .glass-card { transform: none; margin-top: 3rem; }
            .glass-card:hover { transform: none; }
        }
    </style>
</head>
<body>

    <!-- Nav -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-glass fixed-top">
        <div class="container">
            <a class="navbar-brand brand-logo" href="#">Guru Traders ERP</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navMenu">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-3">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="btn btn-outline-light rounded-pill px-4">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('login') }}" class="nav-link text-white fw-medium">Log in</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a href="{{ route('register') }}" class="btn btn-light rounded-pill px-4 text-dark fw-bold shadow-sm">Get Started</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <main class="hero">
        <div class="shape-1"></div>
        <div class="shape-2"></div>
        
        <div class="container relative">
            <div class="row align-items-center">
                <!-- Text Content -->
                <div class="col-lg-6 text-center text-lg-start">
                    <div class="stats-badge">
                        <span class="spinner-grow spinner-grow-sm text-info" role="status"></span>
                        Guru Traders ERP
                    </div>
                    <h1 class="hero-title">Intelligent ERP for<br>Modern Trading.</h1>
                    <p class="hero-subtitle">Optimize your supply chain, oversee advanced metrics, and manage customers effortlessly through one beautiful, unified platform designed for true scalability.</p>
                    
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start">
                        @if (Route::has('login'))
                            @auth
                                <a href="{{ url('/dashboard') }}" class="btn btn-glow">Access Dashboard</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-glow">Sign In Now</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-outline-glow">Create Account</a>
                                @endif
                            @endauth
                        @endif
                    </div>
                </div>

                <!-- Visual Content -->
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="hero-visual pl-5">
                        <div class="glass-card">
                            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom border-secondary pb-3">
                                <div>
                                    <h4 class="mb-0 fw-bold">Live Sales Overview</h4>
                                    <small class="text-secondary">Real-time synchronization</small>
                                </div>
                                <div class="bg-primary bg-opacity-25 p-2 rounded-3 text-primary">
                                    <i class="bi bi-graph-up h4 mb-0"></i>
                                </div>
                            </div>
                            
                            <!-- Fake Chart Lines -->
                            <div class="d-flex align-items-end gap-2 mt-4" style="height: 120px;">
                                <div class="bg-primary rounded-top w-100" style="height: 40%; opacity: 0.5;"></div>
                                <div class="bg-info rounded-top w-100" style="height: 65%; opacity: 0.7;"></div>
                                <div class="bg-primary rounded-top w-100" style="height: 50%; opacity: 0.5;"></div>
                                <div class="bg-info rounded-top w-100" style="height: 85%; opacity: 0.8;"></div>
                                <div class="bg-primary rounded-top w-100" style="height: 70%; opacity: 0.6;"></div>
                                <div class="bg-info rounded-top w-100" style="height: 100%; box-shadow: 0 0 20px rgba(6, 182, 212, 0.4);"></div>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top border-secondary d-flex justify-content-between text-muted small fw-bolder">
                                <span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
