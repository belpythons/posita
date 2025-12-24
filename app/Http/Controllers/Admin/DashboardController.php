<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDataService;
use App\Services\BoxOrderService;
use App\Services\ShopSessionService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminDataService $adminDataService,
        protected BoxOrderService $boxOrderService,
        protected ShopSessionService $shopSessionService
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
        $boxOrderStats = $this->boxOrderService->getOrderStatistics([
            'from_date' => today(),
            'to_date' => today(),
        ]);

        // Get quick stats
        $quickStats = [
            'total_partners' => $this->adminDataService->getPartners(true)->count(),
            'total_employees' => $this->adminDataService->getUsers('employee')->count(),
            'active_sessions' => $todaySessions->where('status', 'open')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'dailySalesTotal' => $dailySalesTotal,
            'pendingBoxOrders' => $pendingBoxOrders,
            'boxOrderStats' => $boxOrderStats,
            'todaySessions' => $todaySessions,
            'quickStats' => $quickStats,
        ]);
    }
}
