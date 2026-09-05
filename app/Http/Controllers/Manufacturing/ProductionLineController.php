<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use App\Models\ProductionLineOutput;
use App\Models\ProductionOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ProductionLineController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-order.view', only: ['index']),
            new Middleware('permission:work-order.edit', only: ['storeOutput']),
        ];
    }

    public function index(): View
    {
        $lines = ProductionLine::query()
            ->where('is_active', true)
            ->with(['outputs' => fn ($q) => $q->whereDate('output_date', now()->toDateString())->latest('id')])
            ->orderBy('name')
            ->get();

        $recent = ProductionLineOutput::query()
            ->with(['line', 'productionOrder', 'creator'])
            ->latest('output_date')
            ->latest('id')
            ->limit(30)
            ->get();

        return view('production-lines.index', [
            'lines' => $lines,
            'recent' => $recent,
            'orders' => ProductionOrder::query()->latest('id')->limit(80)->get(['id', 'order_number']),
        ]);
    }

    public function storeOutput(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'production_line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'production_order_id' => ['nullable', 'integer', 'exists:production_orders,id'],
            'output_date' => ['required', 'date'],
            'pcs' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['created_by'] = $request->user()?->id;
        $data['source'] = 'desk';
        ProductionLineOutput::create($data);

        $line = ProductionLine::query()->find($data['production_line_id']);

        return back()->with('success', "Logged {$data['pcs']} pcs on {$line?->name}.");
    }
}
