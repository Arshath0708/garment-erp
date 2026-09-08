@props(['title' => 'Line scan'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Line scan' }} — {{ config('app.name', 'Garment ERP') }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endif
    <style>
        body.floor-scan {
            background: #111827;
            color: #f9fafb;
            min-height: 100vh;
        }
        .floor-scan .form-control,
        .floor-scan .form-select {
            font-size: 1.15rem;
            min-height: 3rem;
        }
        .floor-scan .btn-scan {
            min-height: 3.25rem;
            font-size: 1.15rem;
        }
        video#floor-camera {
            width: 100%;
            max-height: 220px;
            background: #000;
            border-radius: .5rem;
            object-fit: cover;
        }
    </style>
</head>
<body class="floor-scan">
    <div class="container py-3" style="max-width: 32rem">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="fw-semibold">{{ $title ?? 'Line scan' }}</div>
                <div class="small text-secondary">{{ auth()->user()?->name }}</div>
            </div>
            <a href="{{ route('production-lines.index') }}" class="btn btn-sm btn-outline-light">Desk</a>
        </div>

        @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $flash => $boot)
            @if(session($flash))
                <div class="alert alert-{{ $boot }}">{{ session($flash) }}</div>
            @endif
        @endforeach

        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        {{ $slot }}
    </div>
</body>
</html>
