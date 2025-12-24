<?php //Clay-X

namespace App\Actions\Consignment; //Clay-X

use App\Models\DailyConsignment; //Clay-X
use Illuminate\Support\Facades\DB; //Clay-X
use Illuminate\Validation\ValidationException; //Clay-X
use Exception; //Clay-X

class CloseDailyShopAction //Clay-X
{ //Clay-X
    /**
     * Close the daily shop session and calculate profit.
     * Implements Single Action Class Pattern.
     *
     * @param DailyConsignment $dailyConsignment //Clay-X
     * @param array $itemsData Array of items with remaining_stock (item_id, remaining_stock) //Clay-X
     * @param float $actualCash Actual cash counted at end of day //Clay-X
     * @return array Response with success status and profit data //Clay-X
     * @throws ValidationException //Clay-X
     * @throws Exception //Clay-X
     */
    public function execute(DailyConsignment $dailyConsignment, array $itemsData, float $actualCash): array //Clay-X
    { //Clay-X
        // Clay-X: Validasi bahwa session sudah dibuka
        if ($dailyConsignment->status !== 'open' || $dailyConsignment->closed_at !== null) { //Clay-X
            throw new Exception("Session is not open or already closed."); //Clay-X
        } //Clay-X

        return DB::transaction(function () use ($dailyConsignment, $itemsData, $actualCash) { //Clay-X
            // Clay-X: Ambil semua items yang terkait dengan session ini
            $items = DailyConsignment::where('date', $dailyConsignment->date) //Clay-X
                ->where('input_by_user_id', $dailyConsignment->input_by_user_id) //Clay-X
                ->where('status', '!=', 'closed') //Clay-X
                ->get(); //Clay-X

            // Clay-X: Validate dan hitung profit per item
            $totalProfit = 0; //Clay-X
            $totalRevenue = 0; //Clay-X
            $processedItems = []; //Clay-X

            foreach ($items as $item) { //Clay-X
                // Clay-X: Cari data sisa stok dari request
                $itemData = collect($itemsData)->firstWhere('id', $item->id); //Clay-X

                if (!$itemData) { //Clay-X
                    throw new ValidationException( //Clay-X
                        'Missing stock data for item: ' . $item->product_name //Clay-X
                    ); //Clay-X
                } //Clay-X

                $remainingStock = $itemData['remaining_stock'] ?? 0; //Clay-X

                // Clay-X: Validasi stok sisa tidak boleh negatif
                if ($remainingStock < 0) { //Clay-X
                    throw new ValidationException( //Clay-X
                        'Remaining stock cannot be negative for: ' . $item->product_name //Clay-X
                    ); //Clay-X
                } //Clay-X

                // Clay-X: Validasi stok sisa tidak boleh lebih dari stok awal
                if ($remainingStock > $item->initial_stock) { //Clay-X
                    throw new ValidationException( //Clay-X
                        'Remaining stock cannot exceed initial stock for: ' . $item->product_name //Clay-X
                    ); //Clay-X
                } //Clay-X

                // Clay-X: Hitung kuantitas terjual
                $quantitySold = $item->initial_stock - $remainingStock; //Clay-X

                // Clay-X: Hitung profit per item: (Stok Awal - Stok Sisa) × (Harga Jual - Modal)
                $profitPerUnit = $item->selling_price - $item->base_price; //Clay-X
                $itemProfit = $quantitySold * $profitPerUnit; //Clay-X
                $itemRevenue = $quantitySold * $item->selling_price; //Clay-X

                $totalProfit += $itemProfit; //Clay-X
                $totalRevenue += $itemRevenue; //Clay-X

                // Clay-X: Update item dengan data akhir
                $item->update([ //Clay-X
                    'remaining_stock' => $remainingStock, //Clay-X
                    'quantity_sold' => $quantitySold, //Clay-X
                    'total_profit' => $itemProfit, //Clay-X
                    'total_revenue' => $itemRevenue, //Clay-X
                    'status' => 'closed', //Clay-X
                    'closed_at' => now(), //Clay-X
                ]); //Clay-X

                // Clay-X: Simpan data processed item untuk response
                $processedItems[] = [ //Clay-X
                    'id' => $item->id, //Clay-X
                    'product_name' => $item->product_name, //Clay-X
                    'initial_stock' => $item->initial_stock, //Clay-X
                    'remaining_stock' => $remainingStock, //Clay-X
                    'quantity_sold' => $quantitySold, //Clay-X
                    'selling_price' => $item->selling_price, //Clay-X
                    'base_price' => $item->base_price, //Clay-X
                    'profit' => $itemProfit, //Clay-X
                    'revenue' => $itemRevenue, //Clay-X
                ]; //Clay-X
            } //Clay-X

            // Clay-X: Hitung cash discrepancy
            $expectedCash = $dailyConsignment->start_cash + $totalRevenue; //Clay-X
            $cashDiscrepancy = $actualCash - $expectedCash; //Clay-X

            // Clay-X: Update session record dengan data akhir
            $dailyConsignment->update([ //Clay-X
                'actual_cash' => $actualCash, //Clay-X
                'total_profit' => $totalProfit, //Clay-X
                'total_revenue' => $totalRevenue, //Clay-X
                'closed_at' => now(), //Clay-X
                'status' => 'closed', //Clay-X
                'notes' => "Cash discrepancy: " . ($cashDiscrepancy >= 0 ? '+' : '') . number_format($cashDiscrepancy, 2), //Clay-X
            ]); //Clay-X

            // Clay-X: Return response dengan format sukses dan detail profit
            return [ //Clay-X
                'success' => true, //Clay-X
                'message' => 'Shop closed successfully', //Clay-X
                'data' => [ //Clay-X
                    'total_profit' => $totalProfit, //Clay-X
                    'total_revenue' => $totalRevenue, //Clay-X
                    'start_cash' => $dailyConsignment->start_cash, //Clay-X
                    'actual_cash' => $actualCash, //Clay-X
                    'expected_cash' => $expectedCash, //Clay-X
                    'cash_discrepancy' => $cashDiscrepancy, //Clay-X
                    'items' => $processedItems, //Clay-X
                ], //Clay-X
            ]; //Clay-X
        }); //Clay-X
    } //Clay-X
} //Clay-X
