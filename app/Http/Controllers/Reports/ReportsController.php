<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\ExportDocument;
use App\Models\ProductionOrder;
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
            'purchase_orders'  => PurchaseOrder::query()->count(),
            'export_documents' => ExportDocument::query()->count(),
            'open_shipments'   => ExportDocument::query()->where('status', '!=', 'closed')->count(),
            'closed_shipments' => ExportDocument::query()->where('status', 'closed')->count(),
        ];

        return view('reports.index', compact('stats'));
    }

    /**
     * CSV Pack 1 — Open Purchase Orders
     */
    public function openPosCsv(): StreamedResponse
    {
        $filename = 'open-purchase-orders-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\BF");

            fputcsv($handle, [
                'PO Number',
                'Supplier Company',
                'Order Date',
                'Delivery Date',
                'Total Items',
                'Total Amount',
                'Status',
            ]);

            PurchaseOrder::query()
                ->with(['supplier:id,company_name', 'items:id,purchase_order_id,amount'])
                ->where('status', '!=', 'closed')
                ->where('status', '!=', 'cancelled')
                ->orderByDesc('id')
                ->chunk(200, function ($pos) use ($handle) {
                    foreach ($pos as $po) {
                        fputcsv($handle, [
                            $po->po_num,
                            $po->supplier?->company_name ?? 'N/A',
                            $po->po_date ? $po->po_date->format('Y-m-d') : '',
                            $po->delivery_date ? $po->delivery_date->format('Y-m-d') : '',
                            $po->items->count(),
                            number_format((float) $po->totalAmount(), 2, '.', ''),
                            ucfirst($po->status ?? 'open'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * CSV Pack 2 — Open Exports & Shipments
     */
    public function openExportsCsv(): StreamedResponse
    {
        $filename = 'open-exports-shipments-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\BF");

            fputcsv($handle, [
                'Doc Number',
                'Invoice Number',
                'Buyer Company',
                'Shipment Date',
                'Total Items',
                'Total Amount',
                'Status',
            ]);

            ExportDocument::query()
                ->with(['buyer:id,company_name', 'items:id,export_document_id,amount'])
                ->where('status', '!=', 'closed')
                ->orderByDesc('id')
                ->chunk(200, function ($docs) use ($handle) {
                    foreach ($docs as $doc) {
                        fputcsv($handle, [
                            $doc->doc_num,
                            $doc->invoice_no ?: $doc->doc_num,
                            $doc->buyer?->company_name ?? 'N/A',
                            $doc->shipment_date ? $doc->shipment_date->format('Y-m-d') : '',
                            $doc->items->count(),
                            number_format((float) $doc->totalAmount(), 2, '.', ''),
                            ucfirst($doc->status ?? 'open'),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * CSV Pack 3 — Open CAPA & Quality Defect Holds
     */
    public function openCapaCsv(): StreamedResponse
    {
        $filename = 'open-capa-quality-defects-' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\BF");

            fputcsv($handle, [
                'Production Order Number',
                'Work Order Number',
                'Garment Style',
                'Buyer Style No',
                'Factory Style No',
                'Buyer Company',
                'Current Stage',
                'Target Date',
                'Total Order Qty',
                'QC Rejected Qty',
                'Status',
            ]);

            ProductionOrder::query()
                ->with(['garmentStyle', 'buyer', 'workOrder'])
                ->where(function ($q) {
                    $q->where('qc_rejected_qty', '>', 0)
                      ->orWhere('status', 'Hold')
                      ->orWhere('status', 'In Progress');
                })
                ->orderByDesc('id')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->order_number,
                            $order->workOrder?->wo_num ?? 'N/A',
                            $order->garmentStyle?->style_number ?? 'N/A',
                            $order->garmentStyle?->buyer_style_no ?? '',
                            $order->garmentStyle?->factory_style_no ?? '',
                            $order->buyer?->company_name ?? 'N/A',
                            $order->current_stage,
                            $order->target_date ? $order->target_date->format('Y-m-d') : '',
                            $order->total_qty,
                            $order->qc_rejected_qty ?? 0,
                            $order->status,
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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
