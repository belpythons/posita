<?php

namespace App\Observers;

use App\Models\ShopSession;

class ShopSessionObserver
{
    /**
     * Handle the ShopSession "created" event.
     */
    public function created(ShopSession $shopSession): void
    {
        activity()
            ->performedOn($shopSession)
            ->withProperties(['opening_cash' => $shopSession->opening_cash])
            ->log('Shop Opened');
    }

    /**
     * Handle the ShopSession "updated" event.
     */
    public function updated(ShopSession $shopSession): void
    {
        // Check if shop was closed
        if ($shopSession->wasChanged('closed_at') && $shopSession->closed_at !== null) {
            activity()
                ->performedOn($shopSession)
                ->withProperties([
                    'closing_cash_actual' => $shopSession->closing_cash_actual,
                    'closing_cash_system' => $shopSession->closing_cash_system,
                ])
                ->log('Shop Closed');
        }
    }
}
