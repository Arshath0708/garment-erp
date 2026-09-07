<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bundle {{ $order->order_number }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    @endif
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
        }
        .ticket {
            max-width: 22rem;
            margin: 1.5rem auto;
            border: 2px solid #111;
            padding: 1.25rem;
            text-align: center;
        }
        .ticket .code {
            font-family: ui-monospace, monospace;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: .04em;
        }
    </style>
</head>
<body>
    <div class="no-print text-center py-3">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print ticket</button>
        <a href="{{ route('manufacturing.edit', $order) }}" class="btn btn-outline-secondary">Back</a>
    </div>
    <div class="ticket">
        <div class="small text-uppercase text-secondary mb-1">Sewing line bundle</div>
        <div class="code mb-2">{{ $order->order_number }}</div>
        <svg id="bundle-barcode" class="mb-2"></svg>
        <div>{{ $order->garmentStyle?->style_number }} {{ $order->garmentStyle?->name }}</div>
        <div class="small">{{ $order->workOrder?->wo_num }} · {{ $order->buyer?->company_name }}</div>
        <div class="fw-semibold mt-2">{{ number_format($order->total_qty) }} pcs</div>
        <div class="small text-secondary mt-2">Scan on the phone scan page. One ticket per production order (trial).</div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        JsBarcode('#bundle-barcode', @json($order->order_number), {
            format: 'CODE128',
            width: 2,
            height: 64,
            displayValue: false,
            margin: 0
        });
    </script>
</body>
</html>
