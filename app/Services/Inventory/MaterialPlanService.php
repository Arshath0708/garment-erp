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

            $pcs = $this->pcsForLine($line, $orderQty, $order);
            $required = round((float) $line->qty_per_pc * $pcs, 3);
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
                'size_range'    => $line->sizeRangeLabel(),
                'pcs'           => $pcs,
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
            $requiredByProduct = [];

            foreach ($style->materials()->with('product')->orderBy('sort_order')->get() as $line) {
                if (! $line->product) {
                    continue;
                }
                $pcs = $this->pcsForLine($line, $orderQty, $order);
                $requiredByProduct[$line->product_id] = ($requiredByProduct[$line->product_id] ?? 0)
                    + round((float) $line->qty_per_pc * $pcs, 3);
            }

            foreach ($requiredByProduct as $productId => $required) {
                $product = Product::query()->lockForUpdate()->find($productId);
                if (! $product) {
                    continue;
                }

                $required = round($required, 3);
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

    /**
     * Zipper-by-size: a 5.5" zipper for S–M, a 6" for L–XL. Null range = every size.
     */
    private function pcsForLine($line, int $orderQty, ?ProductionOrder $order): int
    {
        if (! $line->size_from && ! $line->size_to) {
            return $orderQty;
        }

        if (! $order) {
            return $orderQty;
        }

        $sum = 0;
        foreach ($this->sizesInRange($line->size_from, $line->size_to) as $size) {
            $sum += $order->sizeQty('cutting', $size);
        }

        return $sum > 0 ? $sum : $orderQty;
    }

    /**
     * @return list<string>
     */
    private function sizesInRange(?string $from, ?string $to): array
    {
        $all = ProductionOrder::SIZES;
        $fromIdx = $from ? array_search($from, $all, true) : 0;
        $toIdx = $to ? array_search($to, $all, true) : count($all) - 1;
        if ($fromIdx === false) {
            $fromIdx = 0;
        }
        if ($toIdx === false) {
            $toIdx = count($all) - 1;
        }
        if ($fromIdx > $toIdx) {
            [$fromIdx, $toIdx] = [$toIdx, $fromIdx];
        }

        return array_slice($all, $fromIdx, $toIdx - $fromIdx + 1);
    }
}
