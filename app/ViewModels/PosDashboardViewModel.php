<?php

namespace App\ViewModels;

use App\Models\DailyConsignment;
use App\Models\Partner;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Auth;

class PosDashboardViewModel implements Arrayable
{
    public function __construct(
        public $partners = [],
        public $currentSession = null,
        public $dailyStats = []
    ) {
        $this->partners = Partner::where('is_active', true)->get();

        $this->currentSession = DailyConsignment::where('input_by_user_id', Auth::id())
            ->whereNull('closed_at')
            ->whereNotNull('start_cash') // Identify session record
            ->first();

        // Calculate simple stats
        $itemQuery = DailyConsignment::query()
            ->whereDate('date', now()->toDateString())
            ->where('input_by_user_id', Auth::id())
            ->whereNull('start_cash'); // Only product items count for revenue?
        // Or does the session record aggregate revenue?
        // Based on old logic, items have revenue.

        $this->dailyStats = [
            'total_revenue' => (clone $itemQuery)->sum('total_revenue'),
            'items_sold' => (clone $itemQuery)->sum('quantity_sold'),
        ];
    }

    public function toArray()
    {
        return [
            'partners' => $this->partners,
            'currentSession' => $this->currentSession,
            'dailyStats' => $this->dailyStats,
        ];
    }
}
