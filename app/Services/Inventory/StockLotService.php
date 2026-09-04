<?php

namespace App\Services\Inventory;

use App\Models\InwardEntry;
use App\Models\Product;
use App\Models\StockLot;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockLotService
{
    /**
     * Post QC-passed inward lines into a godown as roll/lot rows, and bump product qty.
     *
     * @param  array<int, string>  $lotNumbers  keyed by inward_entry_item id
     */
    public function receiveFromInward(InwardEntry $inward, int $warehouseId, array $lotNumbers = []): void
    {
        $warehouse = Warehouse::query()->whereKey($warehouseId)->where('is_active', true)->firstOrFail();

        $inward->loadMissing('items');

        foreach ($inward->items as $line) {
            $qty = (float) ($line->passed_qty ?? $line->received_qty ?? 0);
            if (! $line->product_id || $qty <= 0) {
                continue;
            }

            $lotNo = trim((string) ($lotNumbers[$line->id] ?? ''));
            if ($lotNo === '') {
                $lotNo = $this->defaultLotNo($inward->inward_no, (int) $line->id);
            }

            $lot = StockLot::query()->firstOrNew([
                'product_id'   => $line->product_id,
                'warehouse_id' => $warehouse->id,
                'lot_no'       => $lotNo,
            ]);

            $lot->qty_on_hand = (float) $lot->qty_on_hand + $qty;
            $lot->received_at = $lot->received_at ?? now();
            $lot->inward_entry_item_id = $lot->inward_entry_item_id ?? $line->id;
            $lot->save();

            Product::query()->whereKey($line->product_id)->increment('qty_on_hand', $qty);
        }
    }

    public function syncProductQtyFromLots(int $productId): void
    {
        $sum = (float) StockLot::query()->where('product_id', $productId)->sum('qty_on_hand');
        Product::query()->whereKey($productId)->update(['qty_on_hand' => $sum]);
    }

    public function defaultLotNo(string $inwardNo, int $itemId): string
    {
        $safe = preg_replace('/[^A-Za-z0-9\-]+/', '-', $inwardNo) ?: 'LOT';

        return substr($safe, 0, 60).'-'.$itemId;
    }

    /**
     * Create an opening / adjustment lot without going through inward.
     */
    public function adjustLot(int $productId, int $warehouseId, string $lotNo, float $qty, ?string $remarks = null): StockLot
    {
        return DB::transaction(function () use ($productId, $warehouseId, $lotNo, $qty, $remarks) {
            $lot = StockLot::query()->firstOrNew([
                'product_id'   => $productId,
                'warehouse_id' => $warehouseId,
                'lot_no'       => $lotNo,
            ]);

            $before = (float) $lot->qty_on_hand;
            $lot->qty_on_hand = $qty;
            $lot->received_at = $lot->received_at ?? now();
            $lot->remarks = $remarks ?? $lot->remarks;
            $lot->save();

            $delta = $qty - $before;
            if (abs($delta) > 0.0005) {
                Product::query()->whereKey($productId)->increment('qty_on_hand', $delta);
            }

            return $lot->refresh();
        });
    }
}
