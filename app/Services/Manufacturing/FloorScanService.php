<?php

namespace App\Services\Manufacturing;

use App\Models\ProductionLine;
use App\Models\ProductionLineOutput;
use App\Models\ProductionOrder;
use App\Models\User;
use App\Models\WorkOrder;
use RuntimeException;

class FloorScanService
{
    public function findOrder(?string $code): ?ProductionOrder
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $order = ProductionOrder::query()
            ->whereRaw('lower(order_number) = ?', [mb_strtolower($code)])
            ->first();

        if ($order) {
            return $order;
        }

        $workOrder = WorkOrder::query()
            ->whereRaw('lower(wo_num) = ?', [mb_strtolower($code)])
            ->first();

        if (! $workOrder) {
            return null;
        }

        return $workOrder->productionOrders()->latest('id')->first();
    }

    /**
     * @return array{output: ProductionLineOutput, order: ProductionOrder, remaining: int}
     */
    public function record(ProductionLine $line, ProductionOrder $order, int $pcs, ?User $user = null): array
    {
        if ($pcs < 1) {
            throw new RuntimeException('Enter at least 1 pc.');
        }

        $stitched = (int) $order->stitching_qty;
        $remaining = max(0, (int) $order->total_qty - $stitched);

        if ($remaining === 0) {
            throw new RuntimeException("{$order->order_number} is already fully stitched ({$order->total_qty} pcs).");
        }

        if ($pcs > $remaining) {
            throw new RuntimeException("Only {$remaining} pc(s) left to stitch on {$order->order_number}.");
        }

        $output = ProductionLineOutput::query()->create([
            'production_line_id' => $line->id,
            'production_order_id' => $order->id,
            'output_date' => now()->toDateString(),
            'pcs' => $pcs,
            'notes' => 'Phone scan',
            'source' => 'scan',
            'created_by' => $user?->id,
        ]);

        $order->stitching_qty = $stitched + $pcs;
        if ($order->current_stage === 'Cutting' || $order->current_stage === 'Printing') {
            $order->current_stage = 'Stitching';
        }
        $order->save();

        app(WorkOrderService::class)->markActualForProduction($order->fresh());

        return [
            'output' => $output,
            'order' => $order->fresh(),
            'remaining' => $remaining - $pcs,
        ];
    }
}
