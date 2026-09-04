<?php

namespace App\Services\Inventory;

use App\Models\ExportDocument;
use App\Models\GarmentStyle;
use App\Models\StyleStock;
use Illuminate\Support\Facades\DB;

class StyleStockService
{
    public function adjust(int $styleId, int $delta): StyleStock
    {
        return DB::transaction(function () use ($styleId, $delta) {
            $row = StyleStock::query()->firstOrCreate(
                ['garment_style_id' => $styleId],
                ['qty_on_hand' => 0]
            );

            $next = max(0, $row->qty_on_hand + $delta);
            $row->update(['qty_on_hand' => $next]);

            return $row->fresh();
        });
    }

    public function qtyOnHand(GarmentStyle $style): int
    {
        return (int) ($style->stock?->qty_on_hand ?? 0);
    }

    /**
     * Packing desk is where cartons are typed. Difference vs last posted
     * carton total becomes ready-garment stock for the style on the shipment.
     */
    public function syncFromCartons(ExportDocument $document): void
    {
        $newTotal = 0;
        foreach ($document->fresh('cartons.lines')->cartons as $carton) {
            foreach ($carton->lines as $line) {
                $newTotal += (int) $line->qty;
            }
        }

        $delta = $newTotal - (int) $document->fg_posted_qty;
        $document->fg_posted_qty = $newTotal;
        $document->save();

        if ($delta === 0) {
            return;
        }

        $style = $this->styleOnDocument($document);
        if ($style) {
            $this->adjust($style->id, $delta);
        }
    }

    public function styleOnDocument(ExportDocument $document): ?GarmentStyle
    {
        $document->loadMissing('items');
        $design = $document->items->first()?->design_no;
        if (! filled($design)) {
            return null;
        }

        return GarmentStyle::query()
            ->where(function ($q) use ($design) {
                $q->where('style_number', $design)
                    ->orWhere('buyer_style_no', $design)
                    ->orWhere('factory_style_no', $design);
            })
            ->first();
    }
}
