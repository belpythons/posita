<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\ShopSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Centralized sales trend method with dynamic date filtering.
     * 
     * @param string $range 'daily' | 'weekly' | 'monthly'
     * @return array Chart data with zero-filled dates
     */
    public function getSalesTrend(string $range = 'daily'): array
    {
        return match ($range) {
            'daily' => $this->getDailyTrend(),
            'weekly' => $this->getWeeklyTrend(),
            'monthly' => $this->getMonthlyTrend(),
            default => $this->getDailyTrend(),
        };
    }

    /**
     * Get daily trend for last 7 days.
     * Zero-fills dates with no transactions.
     */
    private function getDailyTrend(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        // Get session revenue grouped by date
        $sessionRevenue = $this->getSessionRevenueByDate($startDate, $endDate, 'Y-m-d');

        // Get box order revenue grouped by date
        $boxRevenue = $this->getBoxOrderRevenueByDate($startDate, $endDate, 'Y-m-d');

        $data = [];
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $sessionAmount = $sessionRevenue[$key] ?? 0;
            $boxAmount = $boxRevenue[$key] ?? 0;

            $data[] = [
                'label' => $date->translatedFormat('D'), // Sen, Sel, Rab...
                'full_date' => $key,
                'revenue' => $sessionAmount + $boxAmount,
                'session_revenue' => $sessionAmount,
                'box_revenue' => $boxAmount,
            ];
        }

        return [
            'range' => 'daily',
            'period' => '7 hari terakhir',
            'data' => $data,
            'total' => array_sum(array_column($data, 'revenue')),
        ];
    }

    /**
     * Get weekly trend for last 4 weeks.
     * Zero-fills weeks with no transactions.
     */
    private function getWeeklyTrend(): array
    {
        $data = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            // Get session revenue for this week
            $sessionAmount = ShopSession::whereBetween('opened_at', [$weekStart, $weekEnd])
                ->where('status', 'closed')
                ->with('consignments')
                ->get()
                ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

            // Get box order revenue for this week
            $boxAmount = BoxOrder::whereBetween('created_at', [$weekStart, $weekEnd])
                ->whereIn('status', ['paid', 'completed'])
                ->sum('total_price');

            $weekNumber = 4 - $i;
            $data[] = [
                'label' => "Minggu {$weekNumber}",
                'full_date' => $weekStart->format('Y-m-d'),
                'date_range' => $weekStart->format('d M') . ' - ' . $weekEnd->format('d M'),
                'revenue' => (float) $sessionAmount + (float) $boxAmount,
                'session_revenue' => (float) $sessionAmount,
                'box_revenue' => (float) $boxAmount,
            ];
        }

        return [
            'range' => 'weekly',
            'period' => '4 minggu terakhir',
            'data' => $data,
            'total' => array_sum(array_column($data, 'revenue')),
        ];
    }

    /**
     * Get monthly trend for last 12 months.
     * Zero-fills months with no transactions.
     */
    private function getMonthlyTrend(): array
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get session revenue grouped by month
        $sessionRevenue = ShopSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('consignments')
            ->get()
            ->groupBy(fn($s) => Carbon::parse($s->opened_at)->format('Y-m'))
            ->map(fn($group) => $group->sum(fn($s) => $s->consignments->sum('subtotal_income')));

        // Get box order revenue grouped by month
        $boxRevenue = BoxOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->get()
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format('Y-m'))
            ->map(fn($group) => $group->sum('total_price'));

        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $sessionAmount = $sessionRevenue[$key] ?? 0;
            $boxAmount = $boxRevenue[$key] ?? 0;

            $data[] = [
                'label' => $month->translatedFormat('M'), // Jan, Feb, Mar...
                'full_date' => $key,
                'revenue' => (float) $sessionAmount + (float) $boxAmount,
                'session_revenue' => (float) $sessionAmount,
                'box_revenue' => (float) $boxAmount,
            ];
        }

        return [
            'range' => 'monthly',
            'period' => '12 bulan terakhir',
            'data' => $data,
            'total' => array_sum(array_column($data, 'revenue')),
        ];
    }

    /**
     * Get session revenue grouped by date.
     * Uses efficient query with eager loading.
     */
    private function getSessionRevenueByDate(Carbon $startDate, Carbon $endDate, string $format): array
    {
        return ShopSession::whereBetween('opened_at', [$startDate, $endDate])
            ->where('status', 'closed')
            ->with('consignments')
            ->get()
            ->groupBy(fn($s) => Carbon::parse($s->opened_at)->format($format))
            ->map(fn($group) => $group->sum(fn($s) => $s->consignments->sum('subtotal_income')))
            ->toArray();
    }

    /**
     * Get box order revenue grouped by date.
     * Uses efficient query.
     */
    private function getBoxOrderRevenueByDate(Carbon $startDate, Carbon $endDate, string $format): array
    {
        return BoxOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->get()
            ->groupBy(fn($o) => Carbon::parse($o->created_at)->format($format))
            ->map(fn($group) => $group->sum('total_price'))
            ->toArray();
    }

    /**
     * Get comparison between today and yesterday.
     * Combines ShopSession + BoxOrder revenue.
     */
    public function getSalesComparison(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Today's sales
        $todaySessionSales = ShopSession::whereDate('opened_at', $today)
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

        $todayBoxSales = BoxOrder::whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        // Yesterday's sales
        $yesterdaySessionSales = ShopSession::whereDate('opened_at', $yesterday)
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum('subtotal_income'));

        $yesterdayBoxSales = BoxOrder::whereDate('created_at', $yesterday)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        $todaySales = (float) $todaySessionSales + (float) $todayBoxSales;
        $yesterdaySales = (float) $yesterdaySessionSales + (float) $yesterdayBoxSales;

        // Calculate trend percentage
        $trendPercent = $yesterdaySales > 0
            ? round((($todaySales - $yesterdaySales) / $yesterdaySales) * 100, 1)
            : ($todaySales > 0 ? 100 : 0);

        return [
            'today' => $todaySales,
            'yesterday' => $yesterdaySales,
            'trend_percent' => $trendPercent,
            'trend_direction' => $trendPercent > 0 ? 'up' : ($trendPercent < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * Get daily summary for a specific date.
     */
    public function getDailySummary(string $date = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $sessions = ShopSession::whereDate('opened_at', $date)
            ->with(['user', 'consignments'])
            ->get();

        $boxOrders = BoxOrder::whereDate('created_at', $date)
            ->whereIn('status', ['paid', 'completed'])
            ->get();

        $sessionRevenue = $sessions->sum(fn($s) => $s->consignments->sum('subtotal_income'));
        $sessionProfit = $sessions->sum(function ($s) {
            return $s->consignments->sum(fn($c) => ($c->selling_price - $c->base_price) * $c->qty_sold);
        });
        $boxRevenue = $boxOrders->sum('total_price');

        return [
            'date' => $date->format('Y-m-d'),
            'total_sessions' => $sessions->count(),
            'total_revenue' => (float) $sessionRevenue + (float) $boxRevenue,
            'session_revenue' => (float) $sessionRevenue,
            'box_revenue' => (float) $boxRevenue,
            'total_profit' => (float) $sessionProfit + (float) $boxRevenue,
            'total_items_sold' => $sessions->sum(fn($s) => $s->consignments->sum('qty_sold')),
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
            'today_revenue' => (float) BoxOrder::whereDate('created_at', $today)
                ->whereIn('status', ['paid', 'completed'])->sum('total_price'),
            'pending_orders' => BoxOrder::where('status', 'pending')->count(),
            'month_orders' => BoxOrder::where('created_at', '>=', $thisMonth)->count(),
            'month_revenue' => (float) BoxOrder::where('created_at', '>=', $thisMonth)
                ->whereIn('status', ['paid', 'completed'])->sum('total_price'),
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
     * Get global profit calculation.
     */
    public function getGlobalProfit(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's profit
        $todaySessionProfit = ShopSession::whereDate('opened_at', $today)
            ->where('status', 'closed')
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum(fn($c) => ($c->selling_price - $c->base_price) * $c->qty_sold));

        $todayBoxProfit = BoxOrder::whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        // Month's profit
        $monthSessionProfit = ShopSession::where('opened_at', '>=', $thisMonth)
            ->where('status', 'closed')
            ->with('consignments')
            ->get()
            ->sum(fn($s) => $s->consignments->sum(fn($c) => ($c->selling_price - $c->base_price) * $c->qty_sold));

        $monthBoxProfit = BoxOrder::where('created_at', '>=', $thisMonth)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        return [
            'today_profit' => (float) $todaySessionProfit + (float) $todayBoxProfit,
            'today_session_profit' => (float) $todaySessionProfit,
            'today_box_profit' => (float) $todayBoxProfit,
            'month_profit' => (float) $monthSessionProfit + (float) $monthBoxProfit,
            'month_session_profit' => (float) $monthSessionProfit,
            'month_box_profit' => (float) $monthBoxProfit,
        ];
    }

    /**
     * Get session history for dashboard display.
     */
    public function getSessionHistory(int $limit = 20): Collection
    {
        return ShopSession::with(['user', 'consignments'])
            ->orderBy('opened_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get box order history for dashboard display.
     * Sorted: pending first, then by created_at desc.
     */
    public function getBoxOrderHistory(int $limit = 20): Collection
    {
        return BoxOrder::with(['template', 'items'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // ========================================
    // Legacy methods for backward compatibility
    // ========================================

    /**
     * @deprecated Use getSalesTrend('daily') instead
     */
    public function getDailyRevenue(): array
    {
        return $this->getSalesTrend('daily')['data'];
    }

    /**
     * @deprecated Use getSalesTrend('weekly') instead
     */
    public function getWeeklyRevenue(): array
    {
        return $this->getSalesTrend('weekly')['data'];
    }

    /**
     * @deprecated Use getSalesTrend('monthly') instead
     */
    public function getMonthlyRevenue(): array
    {
        return $this->getSalesTrend('monthly')['data'];
    }
}
