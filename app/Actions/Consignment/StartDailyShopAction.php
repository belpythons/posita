<?php

namespace App\Actions\Consignment;

use App\Models\DailyConsignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class StartDailyShopAction
{
    /**
     * Start a new daily shop session.
     *
     * @param User $user
     * @param float $startCash
     * @return DailyConsignment
     * @throws Exception
     */
    public function execute(User $user, float $startCash): DailyConsignment
    {
        // Check if user already has an active session
        $activeSession = DailyConsignment::where('input_by_user_id', $user->id)
            ->whereNull('closed_at')
            ->whereNotNull('start_cash') // Ensure we are looking for a session record, not just an item
            ->exists();

        if ($activeSession) {
            throw new Exception("You already have an active shop session.");
        }

        return DB::transaction(function () use ($user, $startCash) {
            return DailyConsignment::create([
                'date' => now(),
                'input_by_user_id' => $user->id,
                'start_cash' => $startCash,
                'status' => 'open',
                // Explicitly set product fields to null or 0 to distinguish from product items
                // Though they are nullable now, explicit valid values help.
                // But since they are nullable, we can omit them.
            ]);
        });
    }
}
