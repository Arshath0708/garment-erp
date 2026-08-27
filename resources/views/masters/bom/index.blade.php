<x-app-layout>
    <x-slot name="header">BOM &amp; consumption</x-slot>

    <p class="text-body-secondary small mb-4">Order qty × consumption = required. Available stock is live. Shortfall is what to buy.</p>

    <form method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-6">
            <label class="form-label small">Style</label>
            <select name="style_id" class="form-select">
                @foreach ($styles as $s)
                    <option value="{{ $s->id }}" @selected($selected && $selected->id === $s->id)>{{ $s->style_number }} — {{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small">Order qty (pcs)</label>
            <input type="number" min="1" name="qty" class="form-control" value="{{ $orderQty }}">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary">Calculate</button>
        </div>
    </form>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Material</th>
                            <th>Type</th>
                            <th class="text-end">Per pc</th>
                            <th class="text-end">Required</th>
                            <th class="text-end">In stock</th>
                            <th class="text-end">Use stock</th>
                            <th class="text-end">Buy new</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($planRows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td>{{ $row['kind_label'] }}</td>
                                <td class="text-end">{{ number_format($row['qty_per_pc'], 4) }} {{ $row['unit'] }}</td>
                                <td class="text-end">{{ number_format($row['required_qty'], 3) }}</td>
                                <td class="text-end">{{ number_format($row['qty_on_hand'], 3) }}</td>
                                <td class="text-end text-success">{{ number_format($row['use_stock_qty'], 3) }}</td>
                                <td class="text-end {{ $row['buy_qty'] > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                    {{ $row['buy_qty'] > 0 ? number_format($row['buy_qty'], 3) : '0 (enough)' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-body-secondary py-4">
                                    @if ($selected)
                                        No BOM on this style. Open the style and add fabric / accessories.
                                    @else
                                        Create a garment style first.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-body fw-bold py-3 border-0">Styles</div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Style #</th>
                        <th>Name</th>
                        <th>Buyer</th>
                        <th>BOM lines</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($styles as $style)
                        <tr>
                            <td class="fw-bold text-primary">{{ $style->style_number }}</td>
                            <td>{{ $style->name }}</td>
                            <td>{{ $style->buyer?->company_name ?? '—' }}</td>
                            <td>{{ $style->materials->count() }}</td>
                            <td class="text-end">
                                <a href="{{ route('masters.styles.edit', $style) }}" class="btn btn-sm btn-outline-primary">Edit BOM</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-body-secondary">No styles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $styles->links() }}
    </div>
</x-app-layout>
