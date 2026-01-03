<?php
/**
 * Created/Modified by: Muhammad Ammar Alfarabi
 * NIM: 202312056
 * Feature: Close Shop - Service untuk generate laporan PDF
 */
namespace App\Services;

use App\Models\ShopSession;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReportService
{
    /**
     * Generate PDF report for a shop session.
     */
    public function generateSessionReport(ShopSession $session): string
    {
        $session->load(['user', 'consignments.partner']);

        $data = $this->prepareReportData($session);

        $pdf = Pdf::loadView('reports.session-report', $data);

        $filename = 'session-report-' . $session->id . '-' . now()->format('Ymd-His') . '.pdf';
        $path = 'reports/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Stream PDF directly to browser.
     */
    public function streamSessionReport(ShopSession $session)
    {
        $session->load(['user', 'consignments.partner']);

        $data = $this->prepareReportData($session);

        return Pdf::loadView('reports.session-report', $data)
            ->stream('laporan-sesi-' . $session->id . '.pdf');
    }

    /**
     * Prepare data for report view.
     */
    protected function prepareReportData(ShopSession $session): array
    {
        $consignments = $session->consignments;

        // Calculate totals
        $totalQtyInitial = $consignments->sum('qty_initial');
        $totalQtySold = $consignments->sum('qty_sold');
        $totalQtyRemaining = $consignments->sum('qty_remaining');
        $totalIncome = $consignments->sum('subtotal_income');
        $totalBaseValue = $consignments->sum(fn($c) => $c->qty_sold * $c->base_price);
        $totalProfit = $totalIncome - $totalBaseValue;

        // Expected cash = opening cash + total income
        $expectedCash = $session->opening_cash + $totalIncome;
        $cashDifference = ($session->closing_cash_actual ?? 0) - $expectedCash;

        return [
            'session' => $session,
            'consignments' => $consignments,
            'summary' => [
                'total_qty_initial' => $totalQtyInitial,
                'total_qty_sold' => $totalQtySold,
                'total_qty_remaining' => $totalQtyRemaining,
                'total_income' => $totalIncome,
                'total_base_value' => $totalBaseValue,
                'total_profit' => $totalProfit,
                'opening_cash' => $session->opening_cash,
                'expected_cash' => $expectedCash,
                'actual_cash' => $session->closing_cash_actual,
                'cash_difference' => $cashDifference,
            ],
            'generated_at' => now(),
        ];
    }

    /**
     * Generate daily sales report for admin.
     */
    public function generateDailySalesReport(string $date = null): string
    {
        $date = $date ?? today()->toDateString();

        $sessions = ShopSession::whereDate('opened_at', $date)
            ->with(['user', 'consignments.partner'])
            ->get();

        $data = [
            'date' => $date,
            'sessions' => $sessions,
            'total_income' => $sessions->sum(fn($s) => $s->consignments->sum('subtotal_income')),
            'total_sessions' => $sessions->count(),
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.daily-sales-report', $data);

        $filename = 'daily-sales-' . $date . '.pdf';
        $path = 'reports/' . $filename;

        Storage::disk('public')->put($path, $pdf->output());

        return $path;
    }
}
