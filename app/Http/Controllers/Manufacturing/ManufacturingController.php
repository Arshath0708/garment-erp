<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\OrderConfirmation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturingController extends Controller
{
    public function index(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['garmentStyle', 'buyer', 'orderConfirmation'])
            ->orderByDesc('id')
            ->get();

        return view('manufacturing.index', compact('orders'));
    }

    public function create(): View
    {
        $styles = GarmentStyle::query()->where('status', 'Active')->get();
        $salesOrders = OrderConfirmation::query()->orderByDesc('id')->get();

        return view('manufacturing.create', compact('styles', 'salesOrders'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number'          => ['required', 'string', 'max:50', 'unique:production_orders,order_number'],
            'garment_style_id'      => ['required', 'exists:garment_styles,id'],
            'order_confirmation_id' => ['nullable', 'exists:order_confirmations,id'],
            'total_qty'             => ['required', 'integer', 'min:1'],
            'target_date'           => ['required', 'date'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $style = GarmentStyle::findOrFail($validated['garment_style_id']);
        $validated['buyer_id'] = $style->buyer_id;
        $validated['current_stage'] = 'Cutting';
        $validated['status'] = 'In Progress';

        ProductionOrder::create($validated);

        return redirect()->route('manufacturing.index')->with('success', 'Production Order created and connected to Style & Sales Order successfully!');
    }

    public function show(ProductionOrder $order): View
    {
        $order->load(['garmentStyle', 'buyer', 'orderConfirmation']);

        return view('manufacturing.show', compact('order'));
    }

    public function edit(ProductionOrder $order): View
    {
        $styles = GarmentStyle::query()->where('status', 'Active')->get();
        $salesOrders = OrderConfirmation::query()->orderByDesc('id')->get();
        $order->load(['garmentStyle', 'buyer', 'orderConfirmation']);

        return view('manufacturing.edit', compact('order', 'styles', 'salesOrders'));
    }

    public function update(Request $request, ProductionOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'order_number'          => ['required', 'string', 'max:50', Rule::unique('production_orders', 'order_number')->ignore($order->id)],
            'garment_style_id'      => ['required', 'exists:garment_styles,id'],
            'order_confirmation_id' => ['nullable', 'exists:order_confirmations,id'],
            'total_qty'             => ['required', 'integer', 'min:1'],
            'current_stage'         => ['required', 'string'],
            'status'                => ['required', 'string'],
            'target_date'           => ['required', 'date'],
            'cutting_qty'           => ['nullable', 'integer', 'min:0'],
            'stitching_qty'         => ['nullable', 'integer', 'min:0'],
            'finishing_qty'         => ['nullable', 'integer', 'min:0'],
            'qc_passed_qty'         => ['nullable', 'integer', 'min:0'],
            'qc_rejected_qty'       => ['nullable', 'integer', 'min:0'],
            'packing_qty'           => ['nullable', 'integer', 'min:0'],
            'dispatch_qty'          => ['nullable', 'integer', 'min:0'],
            'notes'                 => ['nullable', 'string'],
        ]);

        $style = GarmentStyle::findOrFail($validated['garment_style_id']);
        $validated['buyer_id'] = $style->buyer_id;

        $order->update($validated);

        return redirect()->route('manufacturing.index')->with('success', "Production Order {$order->order_number} updated successfully!");
    }

    public function destroy(ProductionOrder $order): RedirectResponse
    {
        $num = $order->order_number;
        $order->delete();

        return redirect()->route('manufacturing.index')->with('success', "Production Order {$num} deleted successfully!");
    }

    public function updateStage(Request $request, ProductionOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'current_stage'   => ['required', 'string'],
            'cutting_qty'     => ['nullable', 'integer', 'min:0'],
            'stitching_qty'   => ['nullable', 'integer', 'min:0'],
            'finishing_qty'   => ['nullable', 'integer', 'min:0'],
            'qc_passed_qty'   => ['nullable', 'integer', 'min:0'],
            'qc_rejected_qty' => ['nullable', 'integer', 'min:0'],
            'packing_qty'     => ['nullable', 'integer', 'min:0'],
            'dispatch_qty'    => ['nullable', 'integer', 'min:0'],
        ]);

        $order->update(array_filter($validated, fn($val) => !is_null($val)));

        return back()->with('success', "Manufacturing stage updated for {$order->order_number}!");
    }
}
