<?php

namespace App\Observers;

use App\Models\DailyConsignment;

class DailyConsignmentObserver
{
    /**
     * Handle the DailyConsignment "created" event.
     */
    public function created(DailyConsignment $dailyConsignment): void
    {
        // Only log "Shop Opened" for session records (identified by start_cash presence)
        if ($dailyConsignment->start_cash !== null) {
            activity()
                ->performedOn($dailyConsignment)
                ->withProperties(['start_cash' => $dailyConsignment->start_cash])
                ->log('Shop Opened');
        }
    }

    /**
     * Handle the DailyConsignment "updated" event.
     */
    public function updated(DailyConsignment $dailyConsignment): void
    {
        // Check if shop was closed
        if ($dailyConsignment->wasChanged('closed_at') && $dailyConsignment->closed_at !== null) {
            activity()
                ->performedOn($dailyConsignment)
                ->withProperties([
                    'actual_cash' => $dailyConsignment->actual_cash,
                    'total_sales' => $dailyConsignment->total_revenue ?? 0,
                ])
                ->log('Shop Closed');
        }
    }
}
