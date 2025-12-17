<?php

namespace App\Http\Controllers;

use App\Models\DailyConsignment;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    /**
     * Render the POS dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Pos/Dashboard');
    }

    /**
     * Show form to open shop (start daily consignments).
     */
    public function createOpen(): Response
    {
        $partners = Partner::where('is_active', true)->get();

        return Inertia::render('Pos/OpenShop', [
            'partners' => $partners,
        ]);
    }

    /**
     * Store new daily consignments.
     */
    public function storeOpen(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'product_name' => 'required|string|max:255',
            'initial_stock' => 'required|integer|min:0',
            'base_price' => 'required|numeric|min:0',
            'markup' => 'required|integer|min:0', // assuming markup is percentage e.g. 10 for 10%
        ]);

        $basePrice = $validated['base_price'];
        $markupPercent = $validated['markup'];

        // selling_price = base_price + (base_price * markup / 100)
        $sellingPrice = $basePrice + ($basePrice * $markupPercent / 100);

        DailyConsignment::create([
            'date' => now(), // or $request->date if we allow backdating
            'partner_id' => $validated['partner_id'],
            'product_name' => $validated['product_name'],
            'initial_stock' => $validated['initial_stock'],
            'base_price' => $basePrice,
            'markup_percentage' => $markupPercent,
            'selling_price' => $sellingPrice,
            'remaining_stock' => $validated['initial_stock'], // initially same as initial
            'quantity_sold' => 0,
            'total_revenue' => 0,
            'total_profit' => 0,
            'status' => 'open',
            'input_by_user_id' => Auth::id(),
        ]);

        return redirect()->route('pos.dashboard')->with('success', 'Product added to daily supply.');
    }

    /**
     * Show form to close shop (reconcile daily consignments).
     */
    public function createClose(): Response
    {
        // Get today's open consignments
        $consignments = DailyConsignment::with('partner')
            ->whereDate('date', now())
            ->where('status', 'open')
            ->get();

        return Inertia::render('Pos/CloseShop', [
            'consignments' => $consignments,
        ]);
    }

    /**
     * Update consignments for closing.
     */
    public function updateClose(Request $request, DailyConsignment $dailyConsignment)
    {
        $validated = $request->validate([
            'remaining_stock' => 'required|integer|min:0|lte:' . $dailyConsignment->initial_stock,
            'disposition' => 'nullable|in:returned,donated',
        ]);

        $remainingStock = $validated['remaining_stock'];
        $initialStock = $dailyConsignment->initial_stock;
        $sellingPrice = $dailyConsignment->selling_price;
        $basePrice = $dailyConsignment->base_price;

        // quantity_sold = stok awal - sisa
        $quantitySold = $initialStock - $remainingStock;

        // revenue = terjual * harga jual
        $revenue = $quantitySold * $sellingPrice;

        // profit = terjual * (harga jual - modal)
        // or (terjual * harga jual) - (terjual * modal)
        $profit = $quantitySold * ($sellingPrice - $basePrice);

        $dailyConsignment->update([
            'remaining_stock' => $remainingStock,
            'quantity_sold' => $quantitySold,
            'total_revenue' => $revenue,
            'total_profit' => $profit,
            'status' => 'closed',
            'disposition' => $request->disposition,
        ]);

        return redirect()->back()->with('success', 'Item reconciled successfully.');
    }
}
