<?php

namespace App\Http\Controllers;

use App\Models\Buyer;
use App\Models\ExportDocument;
use App\Models\GarmentStyle;
use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['results' => []]);
        }

        $like = '%'.$q.'%';
        $results = [];

        $this->add($results, 'Buyers', Buyer::query()
            ->where(function ($w) use ($like) {
                $w->where('company_name', 'like', $like)->orWhere('display_code', 'like', $like);
            })
            ->orderBy('company_name')
            ->limit(5)
            ->get()
            ->map(fn (Buyer $b) => [
                'label' => ($b->display_code ? $b->display_code.' — ' : '').$b->company_name,
                'url'   => route('masters.buyers.show', $b),
            ]));

        $this->add($results, 'Styles', GarmentStyle::query()
            ->where(function ($w) use ($like) {
                $w->where('style_number', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('buyer_style_no', 'like', $like)
                    ->orWhere('factory_style_no', 'like', $like);
            })
            ->orderBy('style_number')
            ->limit(5)
            ->get()
            ->map(fn (GarmentStyle $s) => [
                'label' => $s->style_number.' — '.$s->name,
                'url'   => route('masters.styles.show', $s),
            ]));

        $this->add($results, 'Enquiries', Inquiry::query()
            ->where(function ($w) use ($like) {
                $w->where('inquiry_no', 'like', $like)->orWhere('buyer_ref', 'like', $like);
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Inquiry $i) => [
                'label' => $i->inquiry_no.($i->buyer_ref ? ' / '.$i->buyer_ref : ''),
                'url'   => route('sales.inquiries.show', $i),
            ]));

        $this->add($results, 'Sales orders', OrderConfirmation::query()
            ->where(function ($w) use ($like) {
                $w->where('oc_num', 'like', $like)->orWhere('buyer_ref', 'like', $like);
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (OrderConfirmation $o) => [
                'label' => $o->oc_num,
                'url'   => route('sales.order-confirmations.show', $o),
            ]));

        $this->add($results, 'Purchase orders', PurchaseOrder::query()
            ->where('po_num', 'like', $like)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (PurchaseOrder $p) => [
                'label' => $p->po_num,
                'url'   => route('procurement.purchase-orders.show', $p),
            ]));

        $this->add($results, 'Work orders', WorkOrder::query()
            ->where('wo_num', 'like', $like)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (WorkOrder $w) => [
                'label' => $w->wo_num,
                'url'   => route('work-orders.show', $w),
            ]));

        $this->add($results, 'Production', ProductionOrder::query()
            ->where('order_number', 'like', $like)
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ProductionOrder $p) => [
                'label' => $p->order_number,
                'url'   => route('manufacturing.show', $p),
            ]));

        $this->add($results, 'Export docs', ExportDocument::query()
            ->where(function ($w) use ($like) {
                $w->where('doc_num', 'like', $like)->orWhere('invoice_no', 'like', $like);
            })
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (ExportDocument $d) => [
                'label' => $d->doc_num,
                'url'   => route('export.documents.show', $d),
            ]));

        return response()->json(['results' => $results]);
    }

    /**
     * @param  list<array{group: string, label: string, url: string}>  $results
     * @param  \Illuminate\Support\Collection<int, array{label: string, url: string}>  $rows
     */
    private function add(array &$results, string $group, $rows): void
    {
        foreach ($rows as $row) {
            $results[] = [
                'group' => $group,
                'label' => $row['label'],
                'url'   => $row['url'],
            ];
        }
    }
}
