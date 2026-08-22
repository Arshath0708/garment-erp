<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\OrderConfirmation;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ManufacturingController extends Controller
{
    public function index(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['garmentStyle', 'buyer', 'orderConfirmation', 'jobber'])
            ->orderByDesc('id')
            ->get();

        return view('manufacturing.index', compact('orders'));
    }

    public function create(): View
    {
        $styles = GarmentStyle::query()->whereIn('status', ['active', 'Active'])->get();

        $salesOrders = OrderConfirmation::query()->orderByDesc('id')->get();
        $jobbers = Supplier::query()->ofParty('jobber')->orderBy('company_name')->get();

        return view('manufacturing.create', compact('styles', 'salesOrders', 'jobbers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->orderRules());

        $style = GarmentStyle::findOrFail($validated['garment_style_id']);
        $validated['buyer_id'] = $style->buyer_id;
        $validated['current_stage'] = 'Cutting';
        $validated['status'] = 'In Progress';
        $validated['job_work_type'] = $validated['job_work_type'] ?? 'in_house';

        $parsed = ProductionOrder::parseSizePayload($request->input('sizes'));
        $validated['size_breakdown'] = $parsed['breakdown'];
        $validated = array_merge($validated, $parsed['totals']);

        ProductionOrder::create($validated);

        return redirect()->route('manufacturing.index')->with('success', 'Production Order created and connected to Style & Sales Order successfully!');
    }

    public function show(ProductionOrder $order): View
    {
        $order->load(['garmentStyle', 'buyer', 'orderConfirmation', 'jobber']);

        return view('manufacturing.show', compact('order'));
    }

    public function edit(ProductionOrder $order): View
    {
        $styles = GarmentStyle::query()->whereIn('status', ['active', 'Active'])->get();

        $salesOrders = OrderConfirmation::query()->orderByDesc('id')->get();
        $jobbers = Supplier::query()->ofParty('jobber')->orderBy('company_name')->get();
        $order->load(['garmentStyle', 'buyer', 'orderConfirmation', 'jobber']);

        return view('manufacturing.edit', compact('order', 'styles', 'salesOrders', 'jobbers'));
    }

    public function update(Request $request, ProductionOrder $order): RedirectResponse
    {
        $validated = $request->validate(array_merge($this->orderRules(), [
            'order_number'  => ['required', 'string', 'max:50', Rule::unique('production_orders', 'order_number')->ignore($order->id)],
            'current_stage' => ['required', 'string'],
            'status'        => ['required', 'string'],
            'qc_rejected_qty' => ['nullable', 'integer', 'min:0'],
        ]));

        $style = GarmentStyle::findOrFail($validated['garment_style_id']);
        $validated['buyer_id'] = $style->buyer_id;

        $parsed = ProductionOrder::parseSizePayload($request->input('sizes'));
        $validated['size_breakdown'] = $parsed['breakdown'];
        $validated = array_merge($validated, $parsed['totals']);

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
            'qc_rejected_qty' => ['nullable', 'integer', 'min:0'],
            'sizes'           => ['nullable', 'array'],
        ]);

        $parsed = ProductionOrder::parseSizePayload($request->input('sizes'));

        $order->update([
            'current_stage'   => $validated['current_stage'],
            'qc_rejected_qty' => $validated['qc_rejected_qty'] ?? $order->qc_rejected_qty,
            'size_breakdown'  => $parsed['breakdown'],
            ...$parsed['totals'],
        ]);

        return back()->with('success', "Manufacturing stage updated for {$order->order_number}!");
    }

    /**
     * Delivery challan for job-work (printing / embroidery / stitching) —
     * size-wise S–5XL grid, same format jobbers use on the floor.
     */
    public function jobWorkChallanPdf(ProductionOrder $order): Response
    {
        $order->load(['garmentStyle', 'buyer', 'orderConfirmation', 'jobber']);
        $company = CompanyProfile::current();
        $stageKey = $order->challanStageKey();
        $sizes = ProductionOrder::SIZES;
        $row = [];
        $total = 0;
        foreach ($sizes as $size) {
            $qty = $order->sizeQty($stageKey, $size);
            $row[$size] = $qty;
            $total += $qty;
        }

        if ($total === 0) {
            $total = (int) $order->{ProductionOrder::STAGE_KEYS[$stageKey]['qty_column']};
        }

        $pdf = Pdf::loadView('manufacturing.job-work-challan', compact(
            'order', 'company', 'sizes', 'row', 'total', 'stageKey'
        ))->setPaper('a4', 'landscape');

        $filename = 'Job-Work-Challan-'.$order->order_number.'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRules(): array
    {
        return [
            'order_number'          => ['required', 'string', 'max:50', Rule::unique('production_orders', 'order_number')],
            'garment_style_id'      => ['required', 'exists:garment_styles,id'],
            'order_confirmation_id' => ['nullable', 'exists:order_confirmations,id'],
            'total_qty'             => ['required', 'integer', 'min:1'],
            'target_date'           => ['required', 'date'],
            'notes'                 => ['nullable', 'string'],
            'job_work_type'         => ['nullable', Rule::in(array_keys(ProductionOrder::JOB_WORK_TYPES))],
            'jobber_id'             => ['nullable', 'exists:suppliers,id'],
            'place_of_supply'       => ['nullable', 'string', 'max:150'],
            'vehicle_no'            => ['nullable', 'string', 'max:50'],
            'driver_name'           => ['nullable', 'string', 'max:100'],
            'challan_no'            => ['nullable', 'string', 'max:50'],
            'sizes'                 => ['nullable', 'array'],
        ];
    }
}
