<?php

namespace App\Services;

use App\Models\ShopSession;
use App\Models\User;
use Carbon\Carbon;

class ShopSessionService
{
    /**
     * Start a new shop session.
     */
    public function startSession(User $user, float $openingCash): ShopSession
    {
        // Check if user already has an open session
        $existingSession = $this->getActiveSession($user);
        if ($existingSession) {
            throw new \Exception('Anda masih memiliki sesi toko yang aktif. Tutup sesi terlebih dahulu.');
        }

        return ShopSession::create([
            'user_id' => $user->id,
            'opened_at' => Carbon::now(),
            'opening_cash' => $openingCash,
            'status' => 'open',
        ]);
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
     */
    public function calculateClosingSummary(ShopSession $session): array
    {
        $session->load('consignments');

        $totalIncome = 0;
        $totalProfit = 0;
        $itemsSummary = [];

        foreach ($session->consignments as $consignment) {
            $income = $consignment->qty_sold * $consignment->selling_price;
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
     */
    public function closeSession(ShopSession $session, float $actualCash, ?string $notes = null): ShopSession
    {
        if ($session->isClosed()) {
            throw new \Exception('Sesi ini sudah ditutup.');
        }

        $summary = $this->calculateClosingSummary($session);

        // Update all consignments' subtotal_income
        foreach ($session->consignments as $consignment) {
            $consignment->calculateSubtotalIncome();
            $consignment->save();
        }

        // Calculate discrepancy
        $discrepancy = $actualCash - $summary['expected_cash'];

        // Generate notes
        $closingNotes = sprintf(
            "Total Pendapatan: Rp %s\nTotal Profit: Rp %s\nKas Awal: Rp %s\nKas Akhir Sistem: Rp %s\nKas Akhir Aktual: Rp %s\nSelisih: Rp %s%s",
            number_format($summary['total_income'], 0, ',', '.'),
            number_format($summary['total_profit'], 0, ',', '.'),
            number_format($session->opening_cash, 0, ',', '.'),
            number_format($summary['expected_cash'], 0, ',', '.'),
            number_format($actualCash, 0, ',', '.'),
            number_format($discrepancy, 0, ',', '.'),
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
