<?php

namespace App\Services\Inventory;

use App\Models\GarmentStyle;
use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaterialPlanService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function preview(GarmentStyle $style, int $orderQty, ?ProductionOrder $order = null): array
    {
        $style->loadMissing('materials.product');
        $order?->loadMissing('materials');

        $rows = [];
        foreach ($style->materials as $line) {
            $product = $line->product;
            if (! $product) {
                continue;
            }

            $required = round((float) $line->qty_per_pc * $orderQty, 3);
            $already = (float) ($order?->materials->firstWhere('product_id', $product->id)?->use_stock_qty ?? 0);
            $available = round((float) $product->qty_on_hand + $already, 3);
            $use = min($required, $available);
            $buy = max(0, round($required - $use, 3));

            $rows[] = [
                'product_id'    => $product->id,
                'name'          => $product->name,
                'item_kind'     => $product->item_kind,
                'kind_label'    => Product::KINDS[$product->item_kind] ?? $product->item_kind,
                'unit'          => $line->unit ?: $product->unit_po,
                'qty_per_pc'    => (float) $line->qty_per_pc,
                'required_qty'  => $required,
                'qty_on_hand'   => $available,
                'use_stock_qty' => $use,
                'buy_qty'       => $buy,
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $input
     */
    public function apply(ProductionOrder $order, array $input = []): void
    {
        $order->loadMissing(['garmentStyle.materials.product', 'materials']);
        $style = $order->garmentStyle;
        if (! $style) {
            return;
        }

        $requested = [];
        foreach ($input as $row) {
            $id = (int) ($row['product_id'] ?? 0);
            if ($id > 0) {
                $requested[$id] = $row;
            }
        }

        DB::transaction(function () use ($order, $style, $requested) {
            $this->releaseLocked($order);

            $orderQty = (int) $order->total_qty;
            $kept = [];

            foreach ($style->materials()->with('product')->orderBy('sort_order')->get() as $line) {
                $product = Product::query()->lockForUpdate()->find($line->product_id);
                if (! $product) {
                    continue;
                }

                $required = round((float) $line->qty_per_pc * $orderQty, 3);
                $onHand = (float) $product->qty_on_hand;
                $want = array_key_exists($product->id, $requested)
                    ? (float) ($requested[$product->id]['use_stock_qty'] ?? $requested[$product->id]['use_stock'] ?? 0)
                    : min($required, $onHand);

                if ($want < 0) {
                    $want = 0;
                }
                if ($want > $required) {
                    $want = $required;
                }
                if ($want > $onHand + 0.0001) {
                    throw ValidationException::withMessages([
                        "materials.{$product->id}.use_stock_qty" => "{$product->name}: use-from-stock ({$want}) cannot exceed available stock ({$onHand}).",
                    ]);
                }

                $use = round($want, 3);
                $buy = max(0, round($required - $use, 3));

                if ($use > 0) {
                    $product->decrement('qty_on_hand', $use);
                }

                $order->materials()->updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'required_qty'  => $required,
                        'use_stock_qty' => $use,
                        'buy_qty'       => $buy,
                    ]
                );
                $kept[] = $product->id;
            }

            $order->materials()->whereNotIn('product_id', $kept)->delete();
        });
    }

    public function release(ProductionOrder $order): void
    {
        DB::transaction(fn () => $this->releaseLocked($order));
    }

    private function releaseLocked(ProductionOrder $order): void
    {
        foreach ($order->materials()->lockForUpdate()->get() as $row) {
            if ((float) $row->use_stock_qty > 0) {
                Product::query()->whereKey($row->product_id)->increment('qty_on_hand', $row->use_stock_qty);
            }
        }

        $order->materials()->delete();
    }
}
