<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manufacturing\WorkOrderRequest;
use App\Models\GarmentStyle;
use App\Models\OrderConfirmation;
use App\Models\TimeAndActionStep;
use App\Models\WorkOrder;
use App\Services\Manufacturing\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class WorkOrderController extends Controller implements HasMiddleware
{
    public function __construct(private readonly WorkOrderService $workOrders)
    {
    }

    public static function middleware(): array
    {
        return [
            new Middleware('permission:work-order.view', only: ['index', 'show', 'timeAndAction']),
            new Middleware('permission:work-order.create', only: ['create', 'store']),
            new Middleware('permission:work-order.edit', only: ['edit', 'update', 'hold']),
            new Middleware('permission:work-order.approve', only: ['release']),
            new Middleware('permission:work-order.delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): View
    {
        $query = WorkOrder::query()
            ->with(['garmentStyle', 'buyer', 'orderConfirmation', 'steps'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('wo_num', 'like', $term)
                    ->orWhereHas('garmentStyle', fn ($s) => $s->where('style_number', 'like', $term)->orWhere('name', 'like', $term))
                    ->orWhereHas('buyer', fn ($b) => $b->where('company_name', 'like', $term));
            });
        }

        $workOrders = $query->paginate(20)->withQueryString();

        return view('work-orders.index', [
            'workOrders' => $workOrders,
            'filters'    => $request->only('search', 'status'),
        ]);
    }

    public function create(): View
    {
        return view('work-orders.create', $this->formData());
    }

    public function store(WorkOrderRequest $request): RedirectResponse
    {
        $workOrder = $this->workOrders->create($request->validated());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', "Work order {$workOrder->wo_num} saved as Draft. Release it before launching production.");
    }

    public function show(WorkOrder $workOrder): View
    {
        $workOrder->load(['garmentStyle', 'buyer', 'orderConfirmation', 'steps', 'productionOrders', 'releasedByUser']);

        return view('work-orders.show', compact('workOrder'));
    }

    public function edit(WorkOrder $workOrder): View
    {
        return view('work-orders.edit', array_merge($this->formData(), [
            'workOrder' => $workOrder,
        ]));
    }

    public function update(WorkOrderRequest $request, WorkOrder $workOrder): RedirectResponse
    {
        $this->workOrders->update($workOrder, $request->validated());

        return redirect()
            ->route('work-orders.show', $workOrder)
            ->with('success', "Work order {$workOrder->wo_num} updated. Time & Action dates follow the target date.");
    }

    public function destroy(WorkOrder $workOrder): RedirectResponse
    {
        try {
            $num = $workOrder->wo_num;
            $this->workOrders->delete($workOrder);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()
            ->route('work-orders.index')
            ->with('success', "Work order {$num} deleted.");
    }

    public function release(WorkOrder $workOrder): RedirectResponse
    {
        try {
            $this->workOrders->release($workOrder);
        } catch (ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->with('warning', collect($e->errors())->flatten()->first());
        }

        return back()->with('success', "Work order {$workOrder->wo_num} released. Production can be launched against it.");
    }

    public function hold(WorkOrder $workOrder): RedirectResponse
    {
        $this->workOrders->hold($workOrder);

        return back()->with('success', "Work order {$workOrder->wo_num} put on hold. Production launch is blocked.");
    }

    public function timeAndAction(Request $request): View
    {
        $lateOnly = $request->boolean('late');

        $steps = TimeAndActionStep::query()
            ->with(['workOrder.garmentStyle', 'workOrder.buyer'])
            ->whereHas('workOrder', fn ($q) => $q->where('status', 'released'))
            ->orderBy('planned_date')
            ->get();

        if ($lateOnly) {
            $steps = $steps->filter(fn (TimeAndActionStep $step) => $step->isLate())->values();
        }

        return view('work-orders.time-and-action', [
            'steps'    => $steps,
            'lateOnly' => $lateOnly,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'styles' => GarmentStyle::query()
                ->with('buyer')
                ->whereIn('status', ['active', 'Active'])
                ->orderBy('style_number')
                ->get(),
            'salesOrders' => OrderConfirmation::query()->with('buyer')->orderByDesc('id')->get(),
        ];
    }
}
