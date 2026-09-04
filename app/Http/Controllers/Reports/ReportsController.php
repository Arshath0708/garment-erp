<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use App\Models\PurchaseOrder;
use App\Services\Reports\OrderProfitService;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function outstanding(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with(['supplier:id,company_name', 'items:id,purchase_order_id,amount'])
            ->latest('id')
            ->get();

        $exportDocuments = ExportDocument::query()
            ->with(['buyer:id,company_name', 'items:id,export_document_id,amount'])
            ->latest('id')
            ->get();

        $supplierOutstanding = $purchaseOrders->sum(fn (PurchaseOrder $po) => $po->totalAmount());
        $buyerOutstanding = $exportDocuments->sum(fn (ExportDocument $doc) => $doc->totalAmount());

        return view('reports.outstanding.index', compact(
            'purchaseOrders',
            'exportDocuments',
            'supplierOutstanding',
            'buyerOutstanding'
        ));
    }

    public function index(): View
    {
        $stats = [
            'purchase_orders' => PurchaseOrder::query()->count(),
            'export_documents' => ExportDocument::query()->count(),
            'open_shipments' => ExportDocument::query()->where('status', '!=', 'closed')->count(),
            'closed_shipments' => ExportDocument::query()->where('status', 'closed')->count(),
        ];

        return view('reports.index', compact('stats'));
    }

    public function orderProfit(OrderProfitService $profit): View
    {
        return view('reports.order-profit', [
            'rows' => $profit->rows(),
        ]);
    }
}

