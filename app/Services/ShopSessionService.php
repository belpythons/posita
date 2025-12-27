<?php

namespace App\Services;

use App\Models\ShopSession;
use App\Models\User;
use Carbon\Carbon;

// Nurita Wahuyuni | 202312061

class ShopSessionService
{
    /**
     * Start a new shop session.
     */
    public function startSession(User $user, float $openingCash, array $consignments = []): ShopSession
    {
        // Check if user already has an open session
        $existingSession = $this->getActiveSession($user);
        if ($existingSession) {
            throw new \Exception('Anda masih memiliki sesi toko yang aktif. Tutup sesi terlebih dahulu.');
        }

        $action = new StartDailyShopAction();
        return $action->execute($user, $openingCash, $consignments);    
    }

    /**
     * Get active (open) session for a user.
     */
    public function getActiveSession(User $user): ?ShopSession
    {
        return ShopSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->with('consignments.partner')
            ->first();
    }

    /**
     * Check if user has an active session.
     */
    public function hasActiveSession(User $user): bool
    {
        return $this->getActiveSession($user) !== null;
    }

    /**
     * Calculate closing summary for a session.
     * Uses already-updated qty_sold and subtotal_income from consignments.
     */
    public function calculateClosingSummary(ShopSession $session): array
    {
        $session->load('consignments');

        $totalIncome = 0;
        $totalProfit = 0;
        $itemsSummary = [];

        foreach ($session->consignments as $consignment) {
            $income = $consignment->subtotal_income;
            $profit = $consignment->qty_sold * ($consignment->selling_price - $consignment->base_price);

            $totalIncome += $income;
            $totalProfit += $profit;

            $itemsSummary[] = [
                'id' => $consignment->id,
                'product_name' => $consignment->product_name,
                'qty_initial' => $consignment->qty_initial,
                'qty_sold' => $consignment->qty_sold,
                'qty_remaining' => $consignment->qty_remaining,
                'selling_price' => $consignment->selling_price,
                'income' => $income,
                'profit' => $profit,
            ];
        }

        // Expected cash = opening cash + total income
        $expectedCash = $session->opening_cash + $totalIncome;

        return [
            'session' => $session,
            'items' => $itemsSummary,
            'total_income' => $totalIncome,
            'total_profit' => $totalProfit,
            'opening_cash' => $session->opening_cash,
            'expected_cash' => $expectedCash,
        ];
    }

    /**
     * Close a shop session.
     * Assumes consignment quantities have already been updated.
     */
    public function closeSession(ShopSession $session, float $actualCash, ?string $notes = null): ShopSession
    {
        if ($session->isClosed()) {
            throw new \Exception('Sesi ini sudah ditutup.');
        }

        // Refresh to get updated consignment data
        $session->refresh();
        $summary = $this->calculateClosingSummary($session);

        // Calculate discrepancy: positive = shortage (kurang), negative = overage (lebih)
        // Formula: expected_cash - actual_cash
        $systemCalculation = $summary['expected_cash'];
        $discrepancy = $systemCalculation - $actualCash;

        // Determine discrepancy status
        $discrepancyStatus = '';
        if ($discrepancy > 0) {
            $discrepancyStatus = ' (Kurang/Shortage)';
        } elseif ($discrepancy < 0) {
            $discrepancyStatus = ' (Lebih/Overage)';
        } else {
            $discrepancyStatus = ' (Pas)';
        }

        // Generate notes
        $closingNotes = sprintf(
            "Total Pendapatan: Rp %s\nTotal Profit: Rp %s\nKas Awal: Rp %s\nKas Akhir Sistem: Rp %s\nKas Akhir Aktual: Rp %s\nSelisih: Rp %s%s%s",
            number_format((float) ($summary['total_income'] ?? 0), 0, ',', '.'),
            number_format((float) ($summary['total_profit'] ?? 0), 0, ',', '.'),
            number_format((float) ($session->opening_cash ?? 0), 0, ',', '.'),
            number_format((float) ($systemCalculation ?? 0), 0, ',', '.'),
            number_format((float) ($actualCash ?? 0), 0, ',', '.'),
            number_format((float) abs($discrepancy ?? 0), 0, ',', '.'),
            $discrepancyStatus,
            $notes ? "\nCatatan: " . $notes : ''
        );

        $session->update([
            'closed_at' => Carbon::now(),
            'closing_cash_system' => $summary['expected_cash'],
            'closing_cash_actual' => $actualCash,
            'status' => 'closed',
            'notes' => $closingNotes,
        ]);

        return $session->fresh();
    }

    /**
     * Get today's sessions.
     */
    public function getTodaySessions(): \Illuminate\Database\Eloquent\Collection
    {
        return ShopSession::whereDate('opened_at', Carbon::today())
            ->with(['user', 'consignments'])
            ->orderBy('opened_at', 'desc')
            ->get();
    }
}
