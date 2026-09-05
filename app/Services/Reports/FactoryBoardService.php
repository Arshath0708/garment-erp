<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\TimeAndActionStep;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FactoryBoardService
{
    /**
     * One row per production order: style, work order, and floor qty on one screen.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(?Carbon $today = null): Collection
    {
        $today ??= now()->startOfDay();

        $orders = ProductionOrder::query()
            ->with(['garmentStyle:id,style_number,name', 'buyer:id,company_name', 'workOrder.steps'])
            ->latest('id')
            ->limit(200)
            ->get();

        return $orders->map(function (ProductionOrder $order) use ($today) {
            $total = (int) $order->total_qty;
            $dispatch = (int) $order->dispatch_qty;
            $lateSteps = $order->workOrder?->lateStepsCount() ?? 0;
            $behindTarget = $order->target_date
                && $order->target_date->lt($today)
                && $dispatch < $total;

            return [
                'order' => $order,
                'wo_num' => $order->workOrder?->wo_num,
                'style' => $order->garmentStyle?->style_number,
                'buyer' => $order->buyer?->company_name,
                'total_qty' => $total,
                'cutting' => (int) $order->cutting_qty,
                'stitching' => (int) $order->stitching_qty,
                'packing' => (int) $order->packing_qty,
                'dispatch' => $dispatch,
                'late_steps' => $lateSteps,
                'is_late' => $lateSteps > 0 || $behindTarget,
                'target' => $order->target_date,
                'status' => $order->status,
            ];
        })->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array{orders: int, cutting: int, stitching: int, packing: int, dispatch: int, late: int}
     */
    public function totals(Collection $rows): array
    {
        return [
            'orders' => $rows->count(),
            'cutting' => (int) $rows->sum('cutting'),
            'stitching' => (int) $rows->sum('stitching'),
            'packing' => (int) $rows->sum('packing'),
            'dispatch' => (int) $rows->sum('dispatch'),
            'late' => $rows->where('is_late', true)->count(),
        ];
    }

    public function lateTnaCount(): int
    {
        return TimeAndActionStep::query()
            ->with('workOrder')
            ->whereHas('workOrder', fn ($q) => $q->where('status', 'released'))
            ->get()
            ->filter(fn (TimeAndActionStep $step) => $step->isLate())
            ->count();
    }

    public function lowStockCount(): int
    {
        return Product::query()
            ->where('reorder_level', '>', 0)
            ->whereColumn('qty_on_hand', '<=', 'reorder_level')
            ->count();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return list<list<string>>
     */
    public function csvLines(Collection $rows): array
    {
        $lines = [[
            'Style',
            'Work order',
            'Production',
            'Buyer',
            'Target qty',
            'Cutting',
            'Stitching',
            'Packing',
            'Dispatch',
            'Late T&A steps',
            'Late',
            'Target date',
            'Status',
        ]];

        foreach ($rows as $row) {
            $lines[] = [
                (string) ($row['style'] ?? ''),
                (string) ($row['wo_num'] ?? ''),
                (string) $row['order']->order_number,
                (string) ($row['buyer'] ?? ''),
                (string) $row['total_qty'],
                (string) $row['cutting'],
                (string) $row['stitching'],
                (string) $row['packing'],
                (string) $row['dispatch'],
                (string) $row['late_steps'],
                $row['is_late'] ? 'yes' : 'no',
                $row['target']?->format('Y-m-d') ?? '',
                (string) ($row['status'] ?? ''),
            ];
        }

        return $lines;
    }
}
