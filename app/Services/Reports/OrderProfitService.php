<?php

namespace App\Services\Reports;

use App\Models\JobWorkVoucher;
use App\Models\ProductionOrder;

class OrderProfitService
{
    /**
     * Plan = approved style costing × order qty.
     * Actual = stock used × costing rate + job-work charges + CM/other × cutting qty.
     *
     * @return list<array{order: ProductionOrder, qty: int, plan_cost: float, actual_cost: float, variance: float, has_costing: bool}>
     */
    public function rows(): array
    {
        $orders = ProductionOrder::query()
            ->with(['garmentStyle.costings.lines', 'materials', 'buyer'])
            ->latest('id')
            ->limit(100)
            ->get();

        $charges = JobWorkVoucher::query()
            ->whereIn('production_order_id', $orders->pluck('id')->filter())
            ->where('type', 'receive')
            ->selectRaw('production_order_id, SUM(charge_amount) as total')
            ->groupBy('production_order_id')
            ->pluck('total', 'production_order_id');

        $rows = [];
        foreach ($orders as $order) {
            $costing = $order->garmentStyle?->latestApprovedCosting();
            $qty = (int) $order->total_qty;
            $cuttingQty = (int) ($order->cutting_qty ?: $qty);
            $plan = $costing ? round((float) $costing->total_cost_per_pc * $qty, 2) : 0.0;

            $actual = 0.0;
            $rateByProduct = [];
            if ($costing) {
                foreach ($costing->lines as $line) {
                    if ($line->product_id) {
                        $rateByProduct[(int) $line->product_id] = (float) $line->rate;
                    }
                }
                $actual += ((float) $costing->cm_cost + (float) $costing->other_cost) * $cuttingQty;
            }

            foreach ($order->materials as $mat) {
                $rate = $rateByProduct[(int) $mat->product_id] ?? 0.0;
                $actual += (float) $mat->use_stock_qty * $rate;
            }

            $actual += (float) ($charges[$order->id] ?? 0);
            $actual = round($actual, 2);

            $rows[] = [
                'order'       => $order,
                'qty'         => $qty,
                'plan_cost'   => $plan,
                'actual_cost' => $actual,
                'variance'    => round($plan - $actual, 2),
                'has_costing' => (bool) $costing,
            ];
        }

        return $rows;
    }
}
