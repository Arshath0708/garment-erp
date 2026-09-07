<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\ProductionLine;
use App\Models\ProductionLineOutput;
use App\Models\ProductionOrder;
use App\Services\Manufacturing\FloorScanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;

class FloorScanController extends Controller implements HasMiddleware
{
    public function __construct(private readonly FloorScanService $scans) {}

    /**
     * @return array<int, Middleware>
     */
    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-order.view', only: ['form', 'ticket']),
            new Middleware('permission:work-order.edit', only: ['store']),
        ];
    }

    public function form(Request $request): View
    {
        $lines = ProductionLine::query()->where('is_active', true)->orderBy('name')->get();
        $selectedLineId = (int) ($request->session()->get('floor_line_id') ?: $lines->firstWhere('name', 'Line 1')?->id ?: $lines->first()?->id);

        $recent = $request->user()
            ? ProductionLineOutput::query()
                ->with(['line', 'productionOrder'])
                ->where('created_by', $request->user()->id)
                ->where('source', 'scan')
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        return view('floor.scan', [
            'lines' => $lines,
            'selectedLineId' => $selectedLineId,
            'recent' => $recent,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'production_line_id' => ['required', 'integer', 'exists:production_lines,id'],
            'code' => ['required', 'string', 'max:80'],
            'pcs' => ['required', 'integer', 'min:1', 'max:5000'],
        ]);

        $request->session()->put('floor_line_id', (int) $data['production_line_id']);

        $line = ProductionLine::query()->findOrFail($data['production_line_id']);
        $order = $this->scans->findOrder($data['code']);

        if (! $order) {
            return back()
                ->withInput()
                ->with('warning', 'No production order matches that code. Scan the bundle ticket, or type the production / work order number.');
        }

        try {
            $result = $this->scans->record($line, $order, (int) $data['pcs'], $request->user());
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        }

        $left = $result['remaining'];

        return back()->with('success', "{$data['pcs']} pc(s) on {$line->name} for {$order->order_number}. {$left} left to stitch.");
    }

    public function ticket(ProductionOrder $order): View
    {
        $order->load(['garmentStyle', 'workOrder', 'buyer']);

        return view('floor.ticket', compact('order'));
    }
}
