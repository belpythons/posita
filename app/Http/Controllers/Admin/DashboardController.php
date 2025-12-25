<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDataService;
use App\Services\BoxOrderService;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Services\ShopSessionService;
use App\Models\ShopSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminDataService $adminDataService,
        protected BoxOrderService $boxOrderService,
        protected ShopSessionService $shopSessionService,
        protected DashboardService $dashboardService,
        protected ReportService $reportService
    ) {
    }

    /**
     * Display admin dashboard.
     */
    public function index(): Response
    {
        // Get today's shop sessions for sales summary
        $todaySessions = $this->shopSessionService->getTodaySessions();

        // Calculate daily sales total
        $dailySalesTotal = $todaySessions->sum(function ($session) {
            return $session->consignments->sum('subtotal_income');
        });

        // Get pending box orders
        $pendingBoxOrders = $this->boxOrderService->getPendingOrders();

        // Get box order statistics
        $boxOrderStats = $this->dashboardService->getBoxOrderStats();

        // Get chart data
        $weeklyRevenue = $this->dashboardService->getWeeklyRevenue();
        $monthlyRevenue = $this->dashboardService->getMonthlyRevenue();

        // Get quick stats
        $quickStats = $this->dashboardService->getQuickStats();

        // Get sales trend (today vs yesterday)
        $salesTrend = $this->dashboardService->getSalesTrend();

        return Inertia::render('Admin/Dashboard', [
            'dailySalesTotal' => $dailySalesTotal,
            'salesTrend' => $salesTrend,
            'pendingBoxOrders' => $pendingBoxOrders,
            'boxOrderStats' => $boxOrderStats,
            'todaySessions' => $todaySessions,
            'quickStats' => $quickStats,
            'weeklyRevenue' => $weeklyRevenue,
            'monthlyRevenue' => $monthlyRevenue,
        ]);
    }

    /**
     * Download daily sales report.
     */
    public function downloadDailyReport(Request $request)
    {
        $date = $request->query('date', today()->toDateString());

        $sessions = ShopSession::whereDate('opened_at', $date)
            ->with(['user', 'consignments.partner'])
            ->get();

        if ($sessions->isEmpty()) {
            return back()->withErrors(['error' => 'Tidak ada data untuk tanggal ini.']);
        }

        $data = [
            'date' => $date,
            'sessions' => $sessions,
            'total_income' => $sessions->sum(fn($s) => $s->consignments->sum('subtotal_income')),
            'total_sessions' => $sessions->count(),
            'generated_at' => now(),
        ];

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily-sales-report', $data)
            ->stream('laporan-harian-' . $date . '.pdf');
    }

    /**
     * Download session report.
     */
    public function downloadSessionReport(ShopSession $session)
    {
        return $this->reportService->streamSessionReport($session);
    }
}
