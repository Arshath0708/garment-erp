<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\GarmentStyle;
use App\Models\ProductionOrder;
use App\Models\OrderConfirmation;
use App\Models\Supplier;
use App\Models\WorkOrder;
use App\Services\Inventory\MaterialPlanService;
use App\Services\Manufacturing\WorkOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManufacturingController extends Controller
{
    public function __construct(
        private readonly MaterialPlanService $materials,
        private readonly WorkOrderService $workOrders,
    ) {
    }

    public function index(Request $request): View
    {
        $orders = ProductionOrder::query()
            ->with(['garmentStyle.comments', 'buyer', 'orderConfirmation', 'jobber', 'workOrder'])
            ->orderByDesc('id')
            ->get();

        return view('manufacturing.index', compact('orders'));
    }

    public function create(Request $request): View
    {
        $styles = GarmentStyle::query()
            ->with('buyer')
            ->whereIn('status', ['active', 'Active'])
            ->orderBy('style_number')
            ->get();

        $salesOrders = OrderConfirmation::query()->with('buyer')->orderByDesc('id')->get();
        $jobbers = Supplier::query()->ofParty('jobber')->orderBy('company_name')->get();
        $workOrders = WorkOrder::query()
            ->released()
            ->with(['garmentStyle', 'buyer', 'orderConfirmation'])
            ->latest('id')
            ->get();
        $selectedWorkOrderId = $request->integer('work_order_id') ?: null;
        $selectedWorkOrder = $selectedWorkOrderId
            ? $workOrders->firstWhere('id', $selectedWorkOrderId)
            : null;

        return view('manufacturing.create', compact(
            'styles',
            'salesOrders',
            'jobbers',
            'workOrders',
            'selectedWorkOrderId',
            'selectedWorkOrder',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(array_merge($this->orderRules(), [
            'work_order_id' => ['required', 'integer', 'exists:work_orders,id'],
        ]));

        $workOrder = $this->releasedWorkOrderOrFail($validated['work_order_id']);
        $this->assertWorkOrderMatchesOrder($workOrder, $validated);

        $style = GarmentStyle::findOrFail($validated['garment_style_id']);
        $validated['buyer_id'] = $style->buyer_id;
        $validated['current_stage'] = 'Cutting';
        $validated['status'] = 'In Progress';
        $validated['job_work_type'] = $validated['job_work_type'] ?? 'in_house';
        $validated['work_order_id'] = $workOrder->id;

        $parsed = $this->validatedSizeBreakdown($request, (int) $validated['total_qty']);
        $validated['size_breakdown'] = $parsed['breakdown'];
        $validated = array_merge($validated, $parsed['totals']);
        unset($validated['materials']);

        $order = ProductionOrder::create($validated);
        $this->materials->apply($order, $request->input('materials', []));
        $this->workOrders->markActualForProduction($order->fresh());

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
        $order->load(['garmentStyle.comments', 'buyer', 'orderConfirmation', 'jobber', 'materials.product']);
        $planRows = $this->materials->preview($order->garmentStyle, (int) $order->total_qty, $order);

        return view('manufacturing.edit', compact('order', 'styles', 'salesOrders', 'jobbers', 'planRows'));
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

        $parsed = $this->validatedSizeBreakdown($request, (int) $validated['total_qty'], $order);
        $validated['size_breakdown'] = $parsed['breakdown'];
        $validated = array_merge($validated, $parsed['totals']);
        $validated['qc_rejected_qty'] = $parsed['breakdown']['qc_passed']['damage'] ?? $validated['qc_rejected_qty'] ?? 0;
        unset($validated['materials']);

        $order->update($validated);
        $this->materials->apply($order->fresh('garmentStyle'), $request->input('materials', []));
        $this->workOrders->markActualForProduction($order->fresh());

        return redirect()->route('manufacturing.index')->with('success', "Production Order {$order->order_number} updated successfully!");
    }

    public function destroy(ProductionOrder $order): RedirectResponse
    {
        $num = $order->order_number;
        $this->materials->release($order);
        $order->delete();

        return redirect()->route('manufacturing.index')->with('success', "Production Order {$num} deleted successfully!");
    }

    public function updateStage(Request $request, ProductionOrder $order): RedirectResponse
    {
        $validated = $request->validate([
            'current_stage'   => ['required', 'string'],
            'qc_rejected_qty' => ['nullable', 'integer', 'min:0'],
            'sizes'           => ['nullable', 'array'],
            'damage'          => ['nullable', 'array'],
            'damage.*'        => ['nullable', 'integer', 'min:0'],
        ]);

        $parsed = $this->validatedSizeBreakdown($request, (int) $order->total_qty, $order);

        $order->update([
            'current_stage'   => $validated['current_stage'],
            'qc_rejected_qty' => $parsed['breakdown']['qc_passed']['damage'] ?? $validated['qc_rejected_qty'] ?? $order->qc_rejected_qty,
            'size_breakdown'  => $parsed['breakdown'],
            ...$parsed['totals'],
        ]);

        $this->workOrders->markActualForProduction($order->fresh());

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
        $sizes = ProductionOrder::SIZES;
        $stageRows = $order->filledStageRows();

        if ($stageRows === []) {
            $stageKey = $order->challanStageKey();
            $sizesMap = [];
            $total = 0;
            foreach ($sizes as $size) {
                $qty = $order->sizeQty($stageKey, $size);
                $sizesMap[$size] = $qty;
                $total += $qty;
            }
            $stageRows = [[
                'key'    => $stageKey,
                'label'  => ProductionOrder::STAGE_KEYS[$stageKey]['label'] ?? $stageKey,
                'sizes'  => $sizesMap,
                'total'  => $total,
                'damage' => $order->stageDamage($stageKey),
            ]];
        }

        $pdf = Pdf::loadView('manufacturing.job-work-challan', compact(
            'order', 'company', 'sizes', 'stageRows'
        ))->setPaper('a4', 'landscape');

        $filename = 'Job-Work-Challan-'.$order->order_number.'.pdf';

        return $pdf->download($filename);
    }

    public function materialPlan(Request $request): JsonResponse
    {
        $request->validate([
            'garment_style_id' => ['required', 'exists:garment_styles,id'],
            'total_qty'        => ['required', 'integer', 'min:1'],
        ]);

        $style = GarmentStyle::findOrFail($request->integer('garment_style_id'));
        $order = $request->filled('order_id')
            ? ProductionOrder::find($request->integer('order_id'))
            : null;

        return response()->json([
            'rows' => $this->materials->preview($style, $request->integer('total_qty'), $order),
        ]);
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
            'materials'                      => ['nullable', 'array'],
            'materials.*.product_id'         => ['nullable', 'integer', 'exists:products,id'],
            'materials.*.use_stock_qty'      => ['nullable', 'numeric', 'min:0'],
            'sizes'                 => ['nullable', 'array'],
            'damage'                => ['nullable', 'array'],
            'damage.*'              => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array{breakdown: array<string, array<string, int>>, totals: array<string, int>}
     */
    private function validatedSizeBreakdown(Request $request, int $orderQty, ?ProductionOrder $order = null): array
    {
        $parsed = ProductionOrder::parseSizePayload($request->input('sizes'), $request->input('damage'));
        $currentStage = $request->input('current_stage', $order?->current_stage ?? 'Cutting');
        $errors = array_merge(
            ProductionOrder::stageFlowErrors($parsed['breakdown'], $orderQty),
            ProductionOrder::stageSelectionErrors($parsed['breakdown'], $currentStage, $order?->size_breakdown)
        );

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $parsed;
    }

    private function releasedWorkOrderOrFail(int $id): WorkOrder
    {
        $workOrder = WorkOrder::query()->find($id);

        if (! $workOrder || $workOrder->status !== 'released') {
            throw ValidationException::withMessages([
                'work_order_id' => 'Release a work order first. Production cannot start on Draft or Hold.',
            ]);
        }

        return $workOrder;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function assertWorkOrderMatchesOrder(WorkOrder $workOrder, array $validated): void
    {
        if ((int) $workOrder->garment_style_id !== (int) $validated['garment_style_id']) {
            throw ValidationException::withMessages([
                'garment_style_id' => 'Style must match the released work order.',
            ]);
        }

        if ($workOrder->order_confirmation_id
            && ! empty($validated['order_confirmation_id'])
            && (int) $workOrder->order_confirmation_id !== (int) $validated['order_confirmation_id']) {
            throw ValidationException::withMessages([
                'order_confirmation_id' => 'Sales order must match the released work order.',
            ]);
        }
    }
}
