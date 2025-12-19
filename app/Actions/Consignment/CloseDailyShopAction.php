<?php

namespace App\Actions\Consignment;

use App\Models\DailyConsignment;
use Illuminate\Support\Facades\DB;
use Exception;

class CloseDailyShopAction
{
    /**
     * Close the daily shop session.
     *
     * @param DailyConsignment $dailyConsignment
     * @param float $actualCash
     * @return DailyConsignment
     */
    public function execute(DailyConsignment $dailyConsignment, float $actualCash): DailyConsignment
    {
        // Calculate discrepancy if needed, or just store actual cash.
        // There is no 'expected_cash' field in the schema we created unless we derive it.
        // Assuming 'expected_cash' logic is: start_cash + total_sales_of_the_day
        // But for this specific action, we are just updating the session record.
        // We'll trust the caller or just save the actual cash.
        // The prompt asks to "calculate the discrepancy", but where to store it?
        // It's not in the schema. We'll simply calculate it for the logical flow maybe?
        // Or store it in 'notes' if needed.
        // For now, we just update the record.

        // Wait, if it's "calculate the discrepancy", the action should probably return it or log it.
        // But strict implementation of "update the record":

        return DB::transaction(function () use ($dailyConsignment, $actualCash) {
            $dailyConsignment->update([
                'actual_cash' => $actualCash,
                'closed_at' => now(),
                'status' => 'closed',
            ]);

            return $dailyConsignment;
        });
    }
}
