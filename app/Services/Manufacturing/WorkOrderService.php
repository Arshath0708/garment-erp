<?php

namespace App\Services\Manufacturing;

use App\Models\GarmentStyle;
use App\Models\NumberSeries;
use App\Models\ProductionOrder;
use App\Models\TimeAndActionStep;
use App\Models\WorkOrder;
use App\Services\NumberSeriesService;
use App\Support\FinancialYear;
use App\Support\StyleCostingGate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkOrderService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WorkOrder
    {
        return DB::transaction(function () use ($data) {
            $style = GarmentStyle::findOrFail($data['garment_style_id']);
            $financialYear = FinancialYear::current();

            $this->ensureNumberSeries($financialYear);

            $workOrder = new WorkOrder([
                'wo_date'                => $data['wo_date'] ?? now()->toDateString(),
                'garment_style_id'       => $style->id,
                'order_confirmation_id'  => ! empty($data['order_confirmation_id']) ? $data['order_confirmation_id'] : null,
                'buyer_id'               => $style->buyer_id,
                'total_qty'              => (int) $data['total_qty'],
                'target_date'            => $data['target_date'],
                'status'                 => 'draft',
                'notes'                  => $data['notes'] ?? null,
            ]);
            $workOrder->financial_year = $financialYear;
            $workOrder->wo_num = $this->numbers->next('work-order', $financialYear);
            $workOrder->save();

            $this->seedSteps($workOrder);

            return $workOrder->fresh('steps');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WorkOrder $workOrder, array $data): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $data) {
            $style = GarmentStyle::findOrFail($data['garment_style_id']);
            $targetChanged = $workOrder->target_date?->toDateString() !== Carbon::parse($data['target_date'])->toDateString();

            $workOrder->update([
                'wo_date'               => $data['wo_date'] ?? $workOrder->wo_date,
                'garment_style_id'      => $style->id,
                'order_confirmation_id' => ! empty($data['order_confirmation_id']) ? $data['order_confirmation_id'] : null,
                'buyer_id'              => $style->buyer_id,
                'total_qty'             => (int) $data['total_qty'],
                'target_date'           => $data['target_date'],
                'notes'                 => $data['notes'] ?? null,
            ]);

            if ($targetChanged) {
                $this->rebuildPlannedDates($workOrder->fresh('steps'));
            }

            return $workOrder->fresh('steps');
        });
    }

    public function release(WorkOrder $workOrder): WorkOrder
    {
        if ($workOrder->status === 'released') {
            return $workOrder;
        }

        $style = $workOrder->garmentStyle ?? GarmentStyle::query()->find($workOrder->garment_style_id);
        if ($style) {
            StyleCostingGate::assertApproved($style);
        }

        $workOrder->update([
            'status'      => 'released',
            'released_at' => now(),
            'released_by' => auth()->id(),
        ]);

        return $workOrder->fresh();
    }

    public function hold(WorkOrder $workOrder): WorkOrder
    {
        $workOrder->update([
            'status' => 'hold',
        ]);

        return $workOrder->fresh();
    }

    public function delete(WorkOrder $workOrder): void
    {
        if ($workOrder->productionOrders()->exists()) {
            throw new RuntimeException('This work order already has a production order. Delete that first.');
        }

        $workOrder->delete();
    }

    public function seedSteps(WorkOrder $workOrder): void
    {
        $order = 0;
        foreach (WorkOrder::TNA_STEPS as $key => $meta) {
            $workOrder->steps()->create([
                'step_key'     => $key,
                'label'        => $meta['label'],
                'sort_order'   => $order++,
                'planned_date' => $workOrder->target_date->copy()->subDays($meta['days_before']),
            ]);
        }
    }

    public function rebuildPlannedDates(WorkOrder $workOrder): void
    {
        foreach ($workOrder->steps as $step) {
            $days = WorkOrder::TNA_STEPS[$step->step_key]['days_before'] ?? 0;
            $step->update([
                'planned_date' => $workOrder->target_date->copy()->subDays($days),
            ]);
        }
    }

    public function markActual(WorkOrder $workOrder, string $stepKey, ?Carbon $date = null): void
    {
        $step = $workOrder->steps()->where('step_key', $stepKey)->first();
        if (! $step || $step->actual_date) {
            return;
        }

        $step->update(['actual_date' => ($date ?? now())->toDateString()]);
    }

    public function markActualForProduction(ProductionOrder $order): void
    {
        $workOrder = $order->workOrder ?: ($order->work_order_id
            ? WorkOrder::query()->with('steps')->find($order->work_order_id)
            : null);

        if (! $workOrder) {
            return;
        }

        $workOrder->loadMissing('steps');

        foreach (WorkOrder::PRODUCTION_TO_TNA as $stageKey => $tnaKey) {
            if ($order->stageSizeTotal($stageKey) > 0 || (int) $order->{$this->qtyColumn($stageKey)} > 0) {
                $this->markActual($workOrder, $tnaKey);
            }
        }
    }

    public function markFabricInwardForOc(?int $orderConfirmationId): void
    {
        if (! $orderConfirmationId) {
            return;
        }

        WorkOrder::query()
            ->where('order_confirmation_id', $orderConfirmationId)
            ->whereIn('status', ['released', 'hold'])
            ->with('steps')
            ->get()
            ->each(fn (WorkOrder $wo) => $this->markActual($wo, 'fabric_inward'));
    }

    private function qtyColumn(string $stageKey): string
    {
        return ProductionOrder::STAGE_KEYS[$stageKey]['qty_column'] ?? 'cutting_qty';
    }

    private function ensureNumberSeries(string $financialYear): void
    {
        NumberSeries::firstOrCreate(
            ['module' => 'work-order', 'financial_year' => $financialYear],
            ['prefix' => 'WO/', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );
    }
}
