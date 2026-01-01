<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\DailyConsignment;
use App\Models\ShopSession;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get database-specific date format expression.
     */
    private function getDateFormat(string $column, string $format): string
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite uses strftime
            return "strftime('{$format}', {$column})";
        }
        
        // MySQL uses DATE_FORMAT
        return "DATE_FORMAT({$column}, '{$format}')";
    }

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
     * OPTIMIZED: Uses database aggregation instead of collection processing.
     */
    private function getDailyTrend(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $period = CarbonPeriod::create($startDate, '1 day', $endDate);

        // Get session revenue using database aggregation (fixes N+1)
        $sessionRevenue = $this->getSessionRevenueAggregated($startDate, $endDate, 'date');

        // Get box order revenue using database aggregation
        $boxRevenue = $this->getBoxOrderRevenueAggregated($startDate, $endDate, 'date');

        $data = [];
        foreach ($period as $date) {
            $key = $date->format('Y-m-d');
            $sessionAmount = $sessionRevenue[$key] ?? 0;
            $boxAmount = $boxRevenue[$key] ?? 0;

            $data[] = [
                'label' => $date->translatedFormat('D'),
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
     * OPTIMIZED: Uses database aggregation instead of collection processing.
     */
    private function getWeeklyTrend(): array
    {
        $data = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = Carbon::now()->subWeeks($i)->startOfWeek();
            $weekEnd = Carbon::now()->subWeeks($i)->endOfWeek();

            // Use database aggregation for session revenue (fixes N+1)
            $sessionAmount = DailyConsignment::query()
                ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
                ->whereBetween('shop_sessions.opened_at', [$weekStart, $weekEnd])
                ->where('shop_sessions.status', 'closed')
                ->sum('daily_consignments.subtotal_income');

            // Get box order revenue
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
     * OPTIMIZED: Uses database aggregation instead of collection processing.
     */
    private function getMonthlyTrend(): array
    {
        $startDate = Carbon::now()->subMonths(11)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Get session revenue grouped by month using database aggregation
        $dateFormatExpr = $this->getDateFormat('shop_sessions.opened_at', '%Y-%m');
        $sessionRevenue = DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->whereBetween('shop_sessions.opened_at', [$startDate, $endDate])
            ->where('shop_sessions.status', 'closed')
            ->selectRaw("{$dateFormatExpr} as month, SUM(daily_consignments.subtotal_income) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Get box order revenue grouped by month
        $boxDateFormatExpr = $this->getDateFormat('created_at', '%Y-%m');
        $boxRevenue = BoxOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->selectRaw("{$boxDateFormatExpr} as month, SUM(total_price) as total")
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $sessionAmount = $sessionRevenue[$key] ?? 0;
            $boxAmount = $boxRevenue[$key] ?? 0;

            $data[] = [
                'label' => $month->translatedFormat('M'),
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
     * Get session revenue aggregated by date using pure SQL.
     * OPTIMIZED: Single query instead of N+1.
     */
    private function getSessionRevenueAggregated(Carbon $startDate, Carbon $endDate, string $groupBy = 'date'): array
    {
        $format = $groupBy === 'date' ? '%Y-%m-%d' : '%Y-%m';
        $dateFormatExpr = $this->getDateFormat('shop_sessions.opened_at', $format);

        return DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->whereBetween('shop_sessions.opened_at', [$startDate, $endDate])
            ->where('shop_sessions.status', 'closed')
            ->selectRaw("{$dateFormatExpr} as period, SUM(daily_consignments.subtotal_income) as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();
    }

    /**
     * Get box order revenue aggregated by date using pure SQL.
     * OPTIMIZED: Single query.
     */
    private function getBoxOrderRevenueAggregated(Carbon $startDate, Carbon $endDate, string $groupBy = 'date'): array
    {
        $format = $groupBy === 'date' ? '%Y-%m-%d' : '%Y-%m';
        $dateFormatExpr = $this->getDateFormat('created_at', $format);

        return BoxOrder::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'completed'])
            ->selectRaw("{$dateFormatExpr} as period, SUM(total_price) as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->toArray();
    }

    /**
     * Get comparison between today and yesterday.
     * OPTIMIZED: Uses database aggregation.
     */
    public function getSalesComparison(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        // Today's sales using database aggregation
        $todaySessionSales = DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->whereDate('shop_sessions.opened_at', $today)
            ->sum('daily_consignments.subtotal_income');

        $todayBoxSales = BoxOrder::whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        // Yesterday's sales using database aggregation
        $yesterdaySessionSales = DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->whereDate('shop_sessions.opened_at', $yesterday)
            ->sum('daily_consignments.subtotal_income');

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
     * Uses eager loading to prevent N+1.
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

        // Use pre-loaded data for calculations
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
     * OPTIMIZED: Minimized queries.
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
     * OPTIMIZED: Uses database aggregation.
     */
    public function getGlobalProfit(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        // Today's profit using aggregation
        $todaySessionProfit = DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->whereDate('shop_sessions.opened_at', $today)
            ->where('shop_sessions.status', 'closed')
            ->selectRaw('SUM((daily_consignments.selling_price - daily_consignments.base_price) * daily_consignments.qty_sold) as profit')
            ->value('profit') ?? 0;

        $todayBoxProfit = BoxOrder::whereDate('created_at', $today)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_price');

        // Month's profit using aggregation
        $monthSessionProfit = DailyConsignment::query()
            ->join('shop_sessions', 'daily_consignments.shop_session_id', '=', 'shop_sessions.id')
            ->where('shop_sessions.opened_at', '>=', $thisMonth)
            ->where('shop_sessions.status', 'closed')
            ->selectRaw('SUM((daily_consignments.selling_price - daily_consignments.base_price) * daily_consignments.qty_sold) as profit')
            ->value('profit') ?? 0;

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
     * Uses eager loading to prevent N+1.
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
