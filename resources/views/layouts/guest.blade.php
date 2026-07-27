<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    <title>{{ config('app.name', 'Guru Traders ERP') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body.modern-login {
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            font-family: 'Source Sans 3', sans-serif;
        }
        .full-height {
            min-height: 100vh;
        }
        .bg-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border-radius: 20px;
        }
        .auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #e0eafc 0%, #cfdef3 100%);
        }
        .auth-card {
            width: 100%;
            max-width: 1000px;
            display: flex;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            background: #fff;
        }
        .auth-image {
            width: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }
        .auth-image::after {
            content: "";
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop') center center / cover;
            opacity: 0.2;
            mix-blend-mode: overlay;
        }
        .auth-image h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            z-index: 2;
        }
        .auth-image p {
            font-size: 1.1rem;
            text-align: center;
            opacity: 0.9;
            z-index: 2;
        }
        .auth-form {
            width: 50%;
            padding: 4rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .auth-brand {
            font-size: 2rem;
            font-weight: 800;
            color: #2b3445;
            margin-bottom: 0.5rem;
        }
        .form-control {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            border: 1px solid #e3e9ef;
            background-color: #f8f9fa;
        }
        .form-control:focus {
            background-color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
        .input-group-text {
            background: transparent;
            border: 1px solid #e3e9ef;
            border-right: none;
            color: #6c757d;
        }
        .input-group > .form-control {
            border-left: none;
        }
        .btn-modern {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            border-radius: 8px;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }
        @media (max-width: 768px) {
            .auth-image { display: none; }
            .auth-form { width: 100%; padding: 2.5rem; }
        }
    </style>
</head>
<body class="modern-login">
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-image">
                <h1>Guru Traders ERP</h1>
                <p>Enterprise Resource Planning made simple. Manage your sales, products, and customers effortlessly.</p>
            </div>

            <div class="auth-form">
                {{ $slot }}
            </div>

        </div>
    </div>
</body>
</html>
