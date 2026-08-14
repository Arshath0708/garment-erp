<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\OrderConfirmation;
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

        // 6-Month Trend Data for Inquiries & Order Confirmations
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->put($date->format('Y-m'), [
                'label' => $date->format('M Y'),
            ]);
        }

        $start = now()->subMonths(5)->startOfMonth();
        $end = now()->endOfMonth();

        $inquiries = Inquiry::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'));

        $orderConfirmations = OrderConfirmation::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['created_at'])
            ->groupBy(fn ($item) => $item->created_at->format('Y-m'));

        $chartLabels = [];
        $inquirySeriesData = [];
        $ocSeriesData = [];

        foreach ($months as $key => $data) {
            $chartLabels[] = $data['label'];
            $inquirySeriesData[] = isset($inquiries[$key]) ? $inquiries[$key]->count() : 0;
            $ocSeriesData[] = isset($orderConfirmations[$key]) ? $orderConfirmations[$key]->count() : 0;
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
            'chartLabels' => $chartLabels,
            'inquirySeriesData' => $inquirySeriesData,
            'ocSeriesData' => $ocSeriesData,
            'inquiryStatusLabels' => $inquiryStatusLabels,
            'inquiryStatusData' => $inquiryStatusData,
        ]);
    }
}
