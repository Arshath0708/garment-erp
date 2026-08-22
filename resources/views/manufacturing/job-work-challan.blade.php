<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Delivery Challan {{ $order->challan_no ?: $order->order_number }}</title>
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #111; }
    table { border-collapse: collapse; width: 100%; }
    .head { width: 100%; margin-bottom: 8px; }
    .co { font-size: 18px; font-weight: bold; letter-spacing: 0.04em; }
    .tag { font-size: 10px; color: #5a3a1a; background: #c4a574; padding: 3px 8px; display: inline-block; }
    .title { text-align: center; font-size: 16px; font-weight: bold; margin: 10px 0 12px; letter-spacing: 0.08em; }
    .meta td { vertical-align: top; padding: 2px 0; }
    .grid th, .grid td { border: 1px solid #222; padding: 5px 4px; font-size: 10px; text-align: center; }
    .grid th { background: #eee; }
    .grid .sku { text-align: left; }
    .foot { margin-top: 18px; font-size: 9px; }
    .sign { text-align: right; margin-top: 40px; }
    .pre { white-space: pre-line; }
</style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                <div class="co">{{ $company->company_name }}</div>
                <div class="tag">Manufacturing &amp; Supply{{ $order->job_work_type !== 'in_house' ? ' · '.$order->jobWorkTypeLabel() : '' }}</div>
                <div class="pre" style="margin-top:4px;font-size:10px">{{ $company->address }}</div>
            </td>
            <td style="text-align:right;width:40%">
                @if($company->tagline)
                    <div style="font-size:10px;color:#555">{{ $company->tagline }}</div>
                @endif
                @if($company->phone)
                    <div style="font-size:10px">{{ $company->phone }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="title">DELIVERY CHALLAN</div>

    <table class="meta" style="margin-bottom:12px">
        <tr>
            <td style="width:55%">
                <strong>M/S:</strong> {{ $order->jobber?->company_name ?: '—' }}<br>
                <strong>Address:</strong> {{ $order->jobber?->address ?: '—' }}<br>
                <strong>Phone:</strong> {{ $order->jobber?->phone ?: '—' }}<br>
                <strong>Place of supply:</strong> {{ $order->place_of_supply ?: '—' }}
            </td>
            <td>
                <strong>Driver Name:</strong> {{ $order->driver_name ?: '—' }}<br>
                <strong>Challan No:</strong> {{ $order->challan_no ?: $order->order_number }}<br>
                <strong>Challan Date:</strong> {{ now()->format('d-m-Y') }}<br>
                <strong>Vehicle No:</strong> {{ $order->vehicle_no ?: '—' }}<br>
                <strong>Production Order:</strong> {{ $order->order_number }}
            </td>
        </tr>
    </table>

    @php
        $style = $order->garmentStyle;
        $sku = $style ? trim($style->style_number.' '.$style->name) : $order->order_number;
        $stageLabel = \App\Models\ProductionOrder::STAGE_KEYS[$stageKey]['label'] ?? $stageKey;
    @endphp

    <table class="grid">
        <thead>
            <tr>
                <th style="width:8%"># No</th>
                <th class="sku">SKU / Style</th>
                @foreach ($sizes as $size)
                    <th>{{ $size }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $style?->style_number ?: '1' }}</td>
                <td class="sku">{{ $sku }}<br><span style="font-size:9px;color:#555">{{ $stageLabel }} · Buyer {{ $order->buyer?->company_name ?: '—' }}</span></td>
                @foreach ($sizes as $size)
                    <td>{{ (int) ($row[$size] ?? 0) }}</td>
                @endforeach
                <td><strong>{{ (int) $total }}</strong></td>
            </tr>
            <tr>
                <td colspan="2" style="text-align:right"><strong>Total</strong></td>
                @foreach ($sizes as $size)
                    <td><strong>{{ (int) ($row[$size] ?? 0) }}</strong></td>
                @endforeach
                <td><strong>{{ (int) $total }}</strong></td>
            </tr>
        </tbody>
    </table>

    <p style="font-size:9px;color:#555;margin-top:8px">
        Size-wise quantities for job work (printing / embroidery / stitching). Goods sent from our floor; our responsibility ceases as soon as the goods leave our premises.
    </p>

    <table class="foot">
        <tr>
            <td style="width:60%">
                <strong>Terms</strong><br>
                1. Subject to local jurisdiction.<br>
                2. Our responsibility ceases as soon as the goods leave our premises.<br>
                3. Job-work goods remain our property.
            </td>
            <td class="sign">
                for {{ $company->company_name }}<br><br><br>
                Authorised Signatory
            </td>
        </tr>
    </table>
</body>
</html>
