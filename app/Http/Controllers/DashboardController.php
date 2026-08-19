<?php

namespace App\Http\Controllers;

use App\Models\ExportDocument;
use App\Models\Inquiry;
use App\Models\OrderConfirmation;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the ERP dashboard with live statistics and trend charts.
     */
    public function index(Request $request): View
    {
        $inquiryCount = Inquiry::count();
        $orderConfirmationCount = OrderConfirmation::count();
        $purchaseOrderCount = PurchaseOrder::count();
        $openShipmentCount = ExportDocument::where('status', '!=', 'closed')->count();

        // Same computation ReportsController::outstanding() uses.
        $supplierOutstanding = PurchaseOrder::with('items:id,purchase_order_id,amount')->get()
            ->sum(fn (PurchaseOrder $po) => $po->totalAmount());
        $buyerOutstanding = ExportDocument::with('items:id,export_document_id,amount')->get()
            ->sum(fn (ExportDocument $doc) => $doc->totalAmount());

        // 6-Month Trend Data across the whole pipeline: Inquiries, Order
        // Confirmations, Purchase Orders, Export Documents.
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->put($date->format('Y-m'), [
                'label' => $date->format('M Y'),
            ]);
        }

        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        $bucketByMonth = fn ($query) => $query
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'));

        $inquiries = $bucketByMonth(Inquiry::query());
        $orderConfirmations = $bucketByMonth(OrderConfirmation::query());
        $purchaseOrders = $bucketByMonth(PurchaseOrder::query());
        $exportDocuments = $bucketByMonth(ExportDocument::query());

        $chartLabels = [];
        $inquirySeriesData = [];
        $ocSeriesData = [];
        $poSeriesData = [];
        $exportDocSeriesData = [];

        foreach ($months as $key => $data) {
            $chartLabels[] = $data['label'];
            $inquirySeriesData[] = isset($inquiries[$key]) ? $inquiries[$key]->count() : 0;
            $ocSeriesData[] = isset($orderConfirmations[$key]) ? $orderConfirmations[$key]->count() : 0;
            $poSeriesData[] = isset($purchaseOrders[$key]) ? $purchaseOrders[$key]->count() : 0;
            $exportDocSeriesData[] = isset($exportDocuments[$key]) ? $exportDocuments[$key]->count() : 0;
        }

        // Status Distribution for Inquiries
        $inquiryStatusCounts = Inquiry::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $inquiryStatusLabels = [];
        $inquiryStatusData = [];
        foreach (Inquiry::STATUSES as $statusKey => $statusLabel) {
            if (isset($inquiryStatusCounts[$statusKey])) {
                $inquiryStatusLabels[] = $statusLabel;
                $inquiryStatusData[] = (int) $inquiryStatusCounts[$statusKey];
            }
        }

        return view('dashboard', [
            'inquiryCount' => $inquiryCount,
            'orderConfirmationCount' => $orderConfirmationCount,
            'purchaseOrderCount' => $purchaseOrderCount,
            'openShipmentCount' => $openShipmentCount,
            'supplierOutstanding' => $supplierOutstanding,
            'buyerOutstanding' => $buyerOutstanding,
            'chartLabels' => $chartLabels,
            'inquirySeriesData' => $inquirySeriesData,
            'ocSeriesData' => $ocSeriesData,
            'poSeriesData' => $poSeriesData,
            'exportDocSeriesData' => $exportDocSeriesData,
            'inquiryStatusLabels' => $inquiryStatusLabels,
            'inquiryStatusData' => $inquiryStatusData,
        ]);
    }
}
