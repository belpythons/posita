<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\ShopSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get weekly revenue data for charts.
     */
    public function getWeeklyRevenue(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $sessions = ShopSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('consignments')
            ->get();

        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayRevenue = $sessions
                ->filter(fn($s) => Carbon::parse($s->opened_at)->isSameDay($date))
                ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

            $data[] = [
                'date' => $date->format('D'),
                'full_date' => $date->format('Y-m-d'),
                'revenue' => $dayRevenue,
            ];
        }

        return $data;
    }

    /**
     * Get monthly revenue data for charts.
     */
    public function getMonthlyRevenue(): array
    {
        $startDate = Carbon::now()->subMonths(5)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $sessions = ShopSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('consignments')
            ->get();

        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthRevenue = $sessions
                ->filter(fn($s) => Carbon::parse($s->opened_at)->format('Y-m') === $month->format('Y-m'))
                ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

            $data[] = [
                'month' => $month->format('M'),
                'full_month' => $month->format('Y-m'),
                'revenue' => $monthRevenue,
            ];
        }

        return $data;
    }

    /**
     * Get daily sales summary.
     */
    public function getDailySummary(string $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $sessions = ShopSession::whereDate('opened_at', $date)
            ->with(['user', 'consignments'])
            ->get();

        $totalRevenue = $sessions->sum(fn($s) => $s->consignments->sum('subtotal_income'));
        $totalProfit = $sessions->sum(function ($s) {
            return $s->consignments->sum(fn($c) => ($c->selling_price - $c->base_price) * $c->qty_sold);
        });
        $totalItemsSold = $sessions->sum(fn($s) => $s->consignments->sum('qty_sold'));

        return [
            'date' => $date->format('Y-m-d'),
            'total_sessions' => $sessions->count(),
            'total_revenue' => $totalRevenue,
            'total_profit' => $totalProfit,
            'total_items_sold' => $totalItemsSold,
            'sessions' => $sessions,
        ];
    }

    /**
     * Get box order statistics.
     */
    public function getBoxOrderStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'today_orders' => BoxOrder::whereDate('created_at', $today)->count(),
            'today_revenue' => BoxOrder::whereDate('created_at', $today)->sum('total_price'),
            'pending_orders' => BoxOrder::where('status', 'pending')->count(),
            'month_orders' => BoxOrder::where('created_at', '>=', $thisMonth)->count(),
            'month_revenue' => BoxOrder::where('created_at', '>=', $thisMonth)->sum('total_price'),
            'upcoming_pickups' => BoxOrder::where('pickup_datetime', '>=', Carbon::now())
                ->where('status', '!=', 'cancelled')
                ->orderBy('pickup_datetime')
                ->limit(5)
                ->with('template')
                ->get(),
        ];
    }

    /**
     * Get quick overview stats.
     */
    public function getQuickStats(): array
    {
        return [
            'total_partners' => \App\Models\Partner::where('is_active', true)->count(),
            'total_employees' => \App\Models\User::where('role', 'employee')->where('is_active', true)->count(),
            'active_sessions' => ShopSession::where('status', 'open')->count(),
            'total_box_templates' => \App\Models\BoxTemplate::where('is_active', true)->count(),
        ];
    }

    /**
     * Get sales trend comparing today vs yesterday.
     * Handles division by zero and provides trend direction.
     */
    public function getSalesTrend(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Get today's closed sessions and calculate sales
        $todaySales = ShopSession::whereDate('opened_at', $today)
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

        // Get yesterday's closed sessions and calculate sales
        $yesterdaySales = ShopSession::whereDate('opened_at', $yesterday)
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

        // Calculate trend percentage, handling division by zero
        if ($yesterdaySales > 0) {
            $trendPercent = round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1);
        } else {
            // If no sales yesterday, show 100% if there are sales today, 0% otherwise
            $trendPercent = $todaySales > 0 ? 100 : 0;
        }

        // Determine trend direction
        $trendDirection = 'flat';
        if ($trendPercent > 0) {
            $trendDirection = 'up';
        } elseif ($trendPercent < 0) {
            $trendDirection = 'down';
        }

        return [
            'today' => (float) $todaySales,
            'yesterday' => (float) $yesterdaySales,
            'trend_percent' => $trendPercent,
            'trend_direction' => $trendDirection,
        ];
    }
}
