<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use App\Models\PurchaseOrder;
use App\Services\Reports\FactoryBoardService;
use App\Services\Reports\OrderProfitService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function factoryBoard(Request $request, FactoryBoardService $board): View
    {
        $lateOnly = $request->boolean('late');
        $rows = $board->rows();

        if ($lateOnly) {
            $rows = $rows->filter(fn (array $row) => $row['is_late'])->values();
        }

        return view('reports.factory-board', [
            'rows' => $rows,
            'totals' => $board->totals($rows),
            'lateOnly' => $lateOnly,
            'lateTna' => $board->lateTnaCount(),
            'lowStock' => $board->lowStockCount(),
        ]);
    }

    public function factoryBoardExport(Request $request, FactoryBoardService $board): StreamedResponse
    {
        $lateOnly = $request->boolean('late');
        $rows = $board->rows();

        if ($lateOnly) {
            $rows = $rows->filter(fn (array $row) => $row['is_late'])->values();
        }

        $lines = $board->csvLines($rows);
        $filename = 'factory-board-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($lines) {
            $out = fopen('php://output', 'w');
            foreach ($lines as $line) {
                fputcsv($out, $line);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
