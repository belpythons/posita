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

        // Get daily summary (includes box orders)
        $dailySummary = $this->dashboardService->getDailySummary();
        $dailySalesTotal = $dailySummary['total_revenue'];

        // Get pending box orders
        $pendingBoxOrders = $this->boxOrderService->getPendingOrders();

        // Get box order statistics
        $boxOrderStats = $this->dashboardService->getBoxOrderStats();

        // Get chart data using centralized method
        $trendData = [
            'daily' => $this->dashboardService->getSalesTrend('daily'),
            'weekly' => $this->dashboardService->getSalesTrend('weekly'),
            'monthly' => $this->dashboardService->getSalesTrend('monthly'),
        ];

        // Get quick stats
        $quickStats = $this->dashboardService->getQuickStats();

        // Get sales comparison (today vs yesterday)
        $salesComparison = $this->dashboardService->getSalesComparison();

        // Get global profit
        $globalProfit = $this->dashboardService->getGlobalProfit();

        // Get history data
        $sessionHistory = $this->dashboardService->getSessionHistory(10);
        $boxOrderHistory = $this->dashboardService->getBoxOrderHistory(10);

        return Inertia::render('Admin/Dashboard', [
            'dailySalesTotal' => $dailySalesTotal,
            'salesTrend' => $salesComparison, // Today vs Yesterday
            'trendData' => $trendData, // Chart data: daily, weekly, monthly
            'pendingBoxOrders' => $pendingBoxOrders,
            'boxOrderStats' => $boxOrderStats,
            'todaySessions' => $todaySessions,
            'quickStats' => $quickStats,
            // Legacy props for backward compatibility
            'dailyRevenue' => $trendData['daily']['data'],
            'weeklyRevenue' => $trendData['weekly']['data'],
            'monthlyRevenue' => $trendData['monthly']['data'],
            'globalProfit' => $globalProfit,
            'sessionHistory' => $sessionHistory,
            'boxOrderHistory' => $boxOrderHistory,
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
