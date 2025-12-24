<?php

namespace App\Http\Controllers;

use App\Models\DailyConsignment; //Clay-X
use App\Models\Partner;
use App\Http\Requests\CloseDailyShopRequest; //Clay-X
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class PosController extends Controller
{
    /**
     * Render the POS dashboard.
     */
    public function createOpen(): Response
    {
        return Inertia::render('Pos/OpenShop', [
            'partners' => Partner::where('is_active', true)
                ->select(['id', 'name'])
                ->get()
        ]);
    }

    /**
     * Menyimpan data produk konsinyasi baru menggunakan StartDailyShopAction.
     * * @param Request $request
     * @param StartDailyShopAction $startDailyShopAction
     * @return RedirectResponse
     */
    public function storeOpen(Request $request, StartDailyShopAction $startDailyShopAction): RedirectResponse
    {
        // 1. Validasi Input Dasar
        // Kita validasi 'markup' hanya boleh 5, 10, atau 15 sesuai permintaan.
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'product_name' => 'required|string|max:255',
            'initial_stock' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'markup' => 'required|in:5,10,15',
            'shop_session_id' => 'nullable|integer', // Optional, tergantung sistem sesi teman Anda
        ]);

        try {

            $dataForAction = [
                'partner_id' => $validated['partner_id'],
                'product_name' => $validated['product_name'],
                'initial_stock' => $validated['initial_stock'],
                'base_price' => $validated['base_price'],
                'markup_percentage' => (int) $validated['markup'],
                'shop_session_id' => $validated['shop_session_id'] ?? null,
            ];

            $startDailyShopAction->execute($request->user(), $dataForAction);

            return redirect()
                ->back()
                ->with('success', 'Produk ' . $validated['product_name'] . ' berhasil ditambahkan!');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function index(): Response
    {
        return Inertia::render('Pos/Dashboard');
    }

    /**
     * Show form to open shop (start daily consignments).
     */
    public function createOpen(): Response
    {
        return Inertia::render('Pos/OpenShop', [
            'partners' => Partner::where('is_active', true)
                ->select(['id', 'name'])
                ->get()
        ]);
    }

    /**
     * Menyimpan data produk konsinyasi baru menggunakan StartDailyShopAction.
     * * @param Request $request
     * @param StartDailyShopAction $startDailyShopAction
     * @return RedirectResponse
     */
    public function storeOpen(Request $request, StartDailyShopAction $startDailyShopAction): RedirectResponse
    {
        // 1. Validasi Input Dasar
        // Kita validasi 'markup' hanya boleh 5, 10, atau 15 sesuai permintaan.
        $validated = $request->validate([
            'partner_id'    => 'required|exists:partners,id',
            'product_name'  => 'required|string|max:255',
            'initial_stock' => 'required|integer|min:1',
            'base_price'    => 'required|numeric|min:0',
            'markup'        => 'required|in:5,10,15', 
            'shop_session_id' => 'nullable|integer', // Optional, tergantung sistem sesi teman Anda
        ]);

        try {

            $dataForAction = [
                'partner_id'        => $validated['partner_id'],
                'product_name'      => $validated['product_name'],
                'initial_stock'     => $validated['initial_stock'],
                'base_price'        => $validated['base_price'],
                'markup_percentage' => (int) $validated['markup'], 
                'shop_session_id'   => $validated['shop_session_id'] ?? null,
            ];

            $startDailyShopAction->execute($request->user(), $dataForAction);

            return redirect()
                ->back()
                ->with('success', 'Produk ' . $validated['product_name'] . ' berhasil ditambahkan!'); 
            
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function index(): Response
    {
        return Inertia::render('Pos/Dashboard');
    }

    /**
     * Get daily consignment items for the current session (API endpoint).
     * //Clay-X
     * @param Request $request //Clay-X
     * @return \Illuminate\Http\JsonResponse //Clay-X
     */
    public function getDailyConsignments(Request $request) //Clay-X
    { //Clay-X
        // Clay-X: Ambil query parameters
        $date = $request->query('date'); //Clay-X
        $userId = $request->query('user_id'); //Clay-X

        // Clay-X: Fetch items dengan status != 'closed'
        $items = DailyConsignment::where('date', $date) //Clay-X
            ->where('input_by_user_id', $userId) //Clay-X
            ->where(function ($query) { //Clay-X
                // Clay-X: Exclude session record (yang punya start_cash tapi no items yet)
                $query->whereNotNull('product_name')->orWhere('status', '=', 'open'); //Clay-X
            }) //Clay-X
            ->where('status', '!=', 'closed') //Clay-X
            ->select([ //Clay-X
                'id', 'product_name', 'initial_stock', 'remaining_stock', //Clay-X
                'selling_price', 'base_price' //Clay-X
            ]) //Clay-X
            ->get(); //Clay-X

        // Clay-X: Return response JSON
        return response()->json($items); //Clay-X
    } //Clay-X

    /**
     * Update (Close) daily shop session using CloseDailyShopAction.
     * //Clay-X
     * @param \App\Http\Requests\CloseDailyShopRequest $request //Clay-X
     * @param DailyConsignment $dailyConsignment //Clay-X
     * @param \App\Actions\Consignment\CloseDailyShopAction $closeDailyShopAction //Clay-X
     * @return \Illuminate\Http\RedirectResponse //Clay-X
     */
    public function updateClose(
        \App\Http\Requests\CloseDailyShopRequest $request, //Clay-X
        DailyConsignment $dailyConsignment, //Clay-X
        \App\Actions\Consignment\CloseDailyShopAction $closeDailyShopAction //Clay-X
    ) { //Clay-X
        // Clay-X: Pastikan session adalah shop session yang valid (memiliki start_cash)
        if ($dailyConsignment->start_cash === null) { //Clay-X
            abort(404, 'Not a shop session.'); //Clay-X
        } //Clay-X

        // Clay-X: Ambil validated data dari request
        $validated = $request->validated(); //Clay-X

        try { //Clay-X
            // Clay-X: Panggil action untuk menutup shop dan hitung profit
            $result = $closeDailyShopAction->execute( //Clay-X
                $dailyConsignment, //Clay-X
                $validated['items'], //Clay-X
                $validated['actual_cash'] //Clay-X
            ); //Clay-X

            // Clay-X: Return response dengan data profit jika request adalah JSON API
            if ($request->expectsJson()) { //Clay-X
                return response()->json($result); //Clay-X
            } //Clay-X

            // Clay-X: Return redirect dengan success message untuk form submission
            return redirect()->route('pos.dashboard')->with('success', $result['message']); //Clay-X
        } catch (\Exception $e) { //Clay-X
            // Clay-X: Handle exception dan return error response
            if ($request->expectsJson()) { //Clay-X
                return response()->json([ //Clay-X
                    'success' => false, //Clay-X
                    'message' => $e->getMessage(), //Clay-X
                ], 422); //Clay-X
            } //Clay-X

            // Clay-X: Return redirect dengan error message
            return redirect()->back()->withErrors(['error' => $e->getMessage()]); //Clay-X
        } //Clay-X
    } //Clay-X
}
