<?php

namespace App\Services\Sales;

use App\Models\Inquiry;
use App\Models\NumberSeries;
use App\Models\OrderConfirmation;
use App\Models\PurchaseOrder;
use App\Services\NumberSeriesService;
use App\Support\FinancialYear;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderConfirmationService
{
    public function __construct(private readonly NumberSeriesService $numbers)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OrderConfirmation
    {
        return DB::transaction(function () use ($data) {
            $oc = new OrderConfirmation($this->headerData($data));
            $this->assignNumber($oc);
            $oc->save();

            $this->syncItems($oc, $data['items'] ?? []);

            return $oc;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OrderConfirmation $oc, array $data): OrderConfirmation
    {
        return DB::transaction(function () use ($oc, $data) {
            $oc->update($this->headerData($data));

            $this->syncItems($oc, $data['items'] ?? []);

            return $oc->refresh();
        });
    }

    /**
     * "Converted from Inquiry: All item data, sizes, colours & costing
     * pre-filled." Only the inquiry's Confirmed items come across — the same
     * confirmed-items check InquiryService::convertToOc() makes, except this
     * now actually builds the OC rather than only flipping item status.
     */
    public function convertFromInquiry(Inquiry $inquiry): OrderConfirmation
    {
        return DB::transaction(function () use ($inquiry) {
            $confirmedItems = $inquiry->items()->where('status', 'confirmed')->with('colours.sizes')->get();

            if ($confirmedItems->isEmpty()) {
                throw new RuntimeException('No items are at Confirmed status yet — nothing to convert.');
            }

            $oc = new OrderConfirmation([
                'mode'                    => 'oc',
                'oc_date'                 => now()->toDateString(),
                'buyer_ref'               => $inquiry->buyer_ref,
                'source_inquiry_id'       => $inquiry->id,
                'buyer_id'                => $inquiry->buyer_id,
                'category_id'             => $inquiry->category_id,
                'document_format_id'      => $inquiry->document_format_id,
                'agent_id'                => $inquiry->agent_id,
                'agent_commission_type'   => $inquiry->agent_commission_type,
                'agent_commission_value'  => $inquiry->agent_commission_value,
                'currency_id'             => $inquiry->currency_id,
                'delivery_details'        => $inquiry->delivery_details,
                'packing_details'         => $inquiry->packing_details,
                'status'                  => 'draft',
            ]);
            $this->assignNumber($oc);
            $oc->save();

            foreach ($confirmedItems as $index => $sourceItem) {
                $ocItem = $oc->items()->create([
                    'sort_order'    => $index,
                    'design_no'     => $sourceItem->design_no,
                    'description'   => $sourceItem->description,
                    'product_id'    => $sourceItem->product_id,
                    'supplier_id'   => $sourceItem->supplier_id,
                    'unit'          => $sourceItem->unit,
                    'fob_value_id'  => $sourceItem->fob_value_id,
                    'price'         => $sourceItem->price,
                    'cost_price'    => $sourceItem->cost_price,
                    'qty'           => $sourceItem->qty,
                    'amount'        => $sourceItem->amount,
                    'remarks'       => $sourceItem->remarks,
                    'custom_values' => $sourceItem->custom_values,
                ]);

                foreach ($sourceItem->colours as $colour) {
                    $ocColour = $ocItem->colours()->create([
                        'colour'     => $colour->colour,
                        'sort_order' => $colour->sort_order,
                    ]);

                    foreach ($colour->sizes as $size) {
                        $ocColour->sizes()->create([
                            'size'       => $size->size,
                            'qty'        => $size->qty,
                            'sort_order' => $size->sort_order,
                        ]);
                    }
                }

                $sourceItem->update(['status' => 'converted_to_oc']);
            }

            $stillOpen = $inquiry->items()->whereNotIn('status', ['converted_to_oc', 'lost'])->exists();

            if (! $stillOpen) {
                $inquiry->update(['status' => 'converted_to_oc', 'converted_at' => now()]);
            }

            return $oc;
        });
    }

    /**
     * Group the selected, not-yet-raised OC items by their own supplier and
     * raise one PO per supplier group — "Raise PO for Selected" in the
     * prototype. Items with no supplier chosen yet, or already on a PO, are
     * silently skipped rather than blocking the others; the caller reports
     * the counts.
     *
     * @param  array<int, int>  $itemIds
     * @return Collection<int, PurchaseOrder>
     */
    public function raisePurchaseOrders(OrderConfirmation $oc, array $itemIds): Collection
    {
        return DB::transaction(function () use ($oc, $itemIds) {
            $items = $oc->items()
                ->whereIn('id', $itemIds)
                ->whereNull('purchase_order_id')
                ->whereNotNull('supplier_id')
                ->with('colours.sizes')
                ->get()
                ->groupBy('supplier_id');

            $financialYear = FinancialYear::current();
            $purchaseOrders = new Collection();

            foreach ($items as $supplierId => $supplierItems) {
                $po = new PurchaseOrder([
                    'order_confirmation_id' => $oc->id,
                    'supplier_id'           => $supplierId,
                    'po_date'               => now()->toDateString(),
                    'delivery_details'      => $oc->delivery_details,
                    'packing_details'       => $oc->packing_details,
                    'status'                => 'draft',
                ]);
                $this->assignPoNumber($po, $financialYear);
                $po->save();

                foreach ($supplierItems->values() as $index => $ocItem) {
                    $poItem = $po->items()->create([
                        'order_confirmation_item_id' => $ocItem->id,
                        'sort_order'                 => $index,
                        'design_no'                   => $ocItem->design_no,
                        'description'                 => $ocItem->description,
                        'product_id'                  => $ocItem->product_id,
                        'unit'                        => $ocItem->unit,
                        'cost_price'                  => $ocItem->cost_price,
                        'qty'                         => $ocItem->qty,
                        'amount'                      => (float) $ocItem->qty * (float) ($ocItem->cost_price ?? 0),
                        'remarks'                     => $ocItem->remarks,
                        'custom_values'               => $ocItem->custom_values,
                    ]);

                    foreach ($ocItem->colours as $colour) {
                        $poColour = $poItem->colours()->create([
                            'colour'     => $colour->colour,
                            'sort_order' => $colour->sort_order,
                        ]);

                        foreach ($colour->sizes as $size) {
                            $poColour->sizes()->create([
                                'size'       => $size->size,
                                'qty'        => $size->qty,
                                'sort_order' => $size->sort_order,
                            ]);
                        }
                    }

                    // Direct property assignment, not update(['purchase_order_id' =>
                    // ...]) — both columns are deliberately absent from
                    // OrderConfirmationItem::$fillable (see its docblock), so a
                    // mass-assignment update() would silently drop them.
                    $ocItem->purchase_order_id = $po->id;
                    $ocItem->raised_at = now();
                    $ocItem->save();
                }

                $purchaseOrders->push($po);
            }

            return $purchaseOrders;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function headerData(array $data): array
    {
        return array_diff_key($data, array_flip(['items']));
    }

    /**
     * `GT/{buyerCode}/{seq}/{FY}` — a global running sequence, the buyer code
     * is spliced in for readability only. NumberSeriesService::format()'s
     * fixed layout can't express this, so the raw number comes from
     * nextNumber() and the string is composed here.
     */
    private function assignNumber(OrderConfirmation $oc): void
    {
        $financialYear = FinancialYear::current();

        NumberSeries::firstOrCreate(
            ['module' => 'oc', 'financial_year' => $financialYear],
            ['prefix' => '', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );

        $number = $this->numbers->nextNumber('oc', $financialYear);
        $buyerCode = $oc->buyer_id ? \App\Models\Buyer::find($oc->buyer_id)?->display_code : null;

        $oc->oc_num = 'GT/'.($buyerCode ?? 'NA')."/{$number}/{$financialYear}";
        $oc->financial_year = $financialYear;
    }

    /** `GT/PO/{seq}/{FY}` — number before the year, per the prototype's own template. */
    private function assignPoNumber(PurchaseOrder $po, string $financialYear): void
    {
        NumberSeries::firstOrCreate(
            ['module' => 'po', 'financial_year' => $financialYear],
            ['prefix' => '', 'padding' => 3, 'current_number' => 0, 'reset_yearly' => true]
        );

        $number = $this->numbers->nextNumber('po', $financialYear);

        $po->po_num = "GT/PO/{$number}/{$financialYear}";
        $po->financial_year = $financialYear;
    }

    /**
     * Rewrite every item, colour and size row from what was posted — same
     * delete-then-recreate call InquiryService::syncItems() makes. Items
     * already raised onto a PO (purchase_order_id set) are left untouched:
     * they no longer belong to the editable form, only to the PO that owns
     * them now.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(OrderConfirmation $oc, array $items): void
    {
        $oc->items()->whereNull('purchase_order_id')->delete();

        $startIndex = $oc->items()->count();

        foreach (array_values($items) as $offset => $itemData) {
            $item = $oc->items()->create([
                'sort_order'    => $startIndex + $offset,
                'design_no'     => $itemData['design_no'] ?? null,
                'description'   => $itemData['description'] ?? null,
                'product_id'    => $itemData['product_id'] ?? null,
                'supplier_id'   => $itemData['supplier_id'] ?? null,
                'unit'          => $itemData['unit'] ?? null,
                'fob_value_id'  => $itemData['fob_value_id'] ?? null,
                'price'         => $itemData['price'] ?? null,
                'cost_price'    => $itemData['cost_price'] ?? null,
                'remarks'       => $itemData['remarks'] ?? null,
                'custom_values' => array_filter((array) ($itemData['custom'] ?? []), fn ($v) => filled($v)) ?: null,
            ]);

            $qty = 0;
            $colours = $itemData['colours'] ?? [['colour' => null, 'sizes' => []]];

            foreach (array_values($colours) as $colourIndex => $colourData) {
                $colour = $item->colours()->create([
                    'colour'     => $colourData['colour'] ?? null,
                    'sort_order' => $colourIndex,
                ]);

                foreach (array_values($colourData['sizes'] ?? []) as $sizeIndex => $sizeData) {
                    $sizeQty = (int) ($sizeData['qty'] ?? 0);

                    if (blank($sizeData['size'] ?? null) && $sizeQty === 0) {
                        continue;
                    }

                    $colour->sizes()->create([
                        'size'       => $sizeData['size'] ?? '',
                        'qty'        => $sizeQty,
                        'sort_order' => $sizeIndex,
                    ]);

                    $qty += $sizeQty;
                }
            }

            $item->update([
                'qty'    => $qty,
                'amount' => round($qty * (float) ($itemData['price'] ?? 0), 2),
            ]);
        }
    }
}
