<?php

namespace App\Actions\Consignment;

use App\Models\ShopSession;
use App\Models\DailyConsignment;
use Illuminate\Support\Facades\DB;
use Exception;

class CloseDailyShopAction
{
    /**
     * Close the daily shop session and calculate profit.
     * Implements Single Action Class Pattern.
     *
     * @param ShopSession $shopSession The shop session to close
     * @param array $itemsData Array of items with remaining_stock [{id, remaining_stock}, ...]
     * @param float $actualCash Actual cash counted at end of day
     * @return array Response with success status and profit data
     * @throws Exception
     */
    public function execute(ShopSession $shopSession, array $itemsData, float $actualCash): array
    {
        // Validasi bahwa session masih open
        if ($shopSession->status !== 'open') {
            throw new Exception("Session sudah ditutup atau bukan status 'open'.");
        }

        if ($shopSession->closed_at !== null) {
            throw new Exception("Session sudah pernah ditutup sebelumnya.");
        }

        return DB::transaction(function () use ($shopSession, $itemsData, $actualCash) {
            // Ambil semua items yang berelasi dengan session ini
            $consignments = $shopSession->consignments()->where('status', 'open')->get();

            if ($consignments->isEmpty()) {
                throw new Exception("Tidak ada item konsinyasi dalam sesi ini.");
            }

            // Index items data by id for quick lookup
            $itemsDataById = collect($itemsData)->keyBy('id');

            $totalProfit = 0;
            $totalRevenue = 0;
            $processedItems = [];

            foreach ($consignments as $item) {
                // Cari data sisa stok dari request
                $itemData = $itemsDataById->get($item->id);

                if (!$itemData) {
                    throw new Exception(
                        "Data stok sisa tidak ditemukan untuk item: {$item->product_name} (ID: {$item->id})"
                    );
                }

                $remainingStock = (int) ($itemData['remaining_stock'] ?? 0);

                // Validasi stok sisa tidak boleh negatif
                if ($remainingStock < 0) {
                    throw new Exception(
                        "Stok sisa tidak boleh negatif untuk: {$item->product_name}"
                    );
                }

                // Validasi stok sisa tidak boleh lebih dari stok awal
                if ($remainingStock > $item->initial_stock) {
                    throw new Exception(
                        "Stok sisa ({$remainingStock}) tidak boleh melebihi stok awal ({$item->initial_stock}) untuk: {$item->product_name}"
                    );
                }

                // Hitung kuantitas terjual
                $quantitySold = $item->initial_stock - $remainingStock;

                // Hitung profit per item: (Qty Sold) × (Harga Jual - Modal)
                $profitPerUnit = $item->selling_price - $item->base_price;
                $itemProfit = $quantitySold * $profitPerUnit;
                $itemRevenue = $quantitySold * $item->selling_price;

                $totalProfit += $itemProfit;
                $totalRevenue += $itemRevenue;

                // Update item (DailyConsignment) dengan data akhir
                $item->update([
                    'remaining_stock' => $remainingStock,
                    'quantity_sold' => $quantitySold,
                    'total_profit' => $itemProfit,
                    'total_revenue' => $itemRevenue,
                    'status' => 'closed',
                ]);

                // Simpan data processed item untuk response
                $processedItems[] = [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'initial_stock' => $item->initial_stock,
                    'remaining_stock' => $remainingStock,
                    'quantity_sold' => $quantitySold,
                    'selling_price' => (float) $item->selling_price,
                    'base_price' => (float) $item->base_price,
                    'profit' => $itemProfit,
                    'revenue' => $itemRevenue,
                ];
            }

            // Hitung cash discrepancy
            $expectedCash = (float) $shopSession->start_cash + $totalRevenue;
            $cashDiscrepancy = $actualCash - $expectedCash;

            // Buat summary notes dengan informasi profit/revenue/discrepancy
            $notes = sprintf(
                "Total Revenue: Rp %s | Total Profit: Rp %s | Expected Cash: Rp %s | Actual Cash: Rp %s | Discrepancy: %sRp %s",
                number_format($totalRevenue, 0, ',', '.'),
                number_format($totalProfit, 0, ',', '.'),
                number_format($expectedCash, 0, ',', '.'),
                number_format($actualCash, 0, ',', '.'),
                $cashDiscrepancy >= 0 ? '+' : '-',
                number_format(abs($cashDiscrepancy), 0, ',', '.')
            );

            // Update ShopSession dengan data akhir
            // Catatan: actual_cash dan notes disimpan karena tidak ada kolom total_profit/total_revenue
            $shopSession->update([
                'actual_cash' => $actualCash,
                'closed_at' => now(),
                'status' => 'closed',
                'notes' => $notes,
            ]);

            // Return response dengan format sukses dan detail profit
            return [
                'success' => true,
                'message' => 'Toko berhasil ditutup!',
                'data' => [
                    'shop_session_id' => $shopSession->id,
                    'total_profit' => $totalProfit,
                    'total_revenue' => $totalRevenue,
                    'start_cash' => (float) $shopSession->start_cash,
                    'actual_cash' => $actualCash,
                    'expected_cash' => $expectedCash,
                    'cash_discrepancy' => $cashDiscrepancy,
                    'items_count' => count($processedItems),
                    'items' => $processedItems,
                ],
            ];
        });
    }
}
