<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\DailyConsignment;
use App\Services\AdminDataService;
use App\Services\ConsignmentService;
use App\Services\ShopSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConsignmentController extends Controller
{
    public function __construct(
        protected ConsignmentService $consignmentService,
        protected ShopSessionService $shopSessionService,
        protected AdminDataService $adminDataService
    ) {
    }

    /**
     * Display consignment management page.
     */
    public function index(): Response
    {
        $user = auth()->user();
        $activeSession = $this->shopSessionService->getActiveSession($user);

        // Get partners and product templates for dropdowns
        $partners = $this->adminDataService->getPartners(true);
        $productTemplates = $this->adminDataService->getProductTemplates();

        // Get today's consignments if session exists
        $consignments = $activeSession
            ? $this->consignmentService->getSessionConsignments($activeSession)
            : collect([]);

        // Get session summary
        $summary = $activeSession
            ? $this->consignmentService->getSessionSummary($activeSession)
            : null;

        return Inertia::render('Pos/Consignment', [
            'hasActiveSession' => $activeSession !== null,
            'activeSession' => $activeSession,
            'partners' => $partners,
            'productTemplates' => $productTemplates,
            'consignments' => $consignments,
            'summary' => $summary,
        ]);
    }

    /**
     * Store a new consignment item.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'partner_id' => 'required|exists:partners,id',
            'product_name' => 'required|string|max:255',
            'qty_initial' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'markup_percent' => 'required|in:5,10,15',
        ]);

        $user = auth()->user();
        $activeSession = $this->shopSessionService->getActiveSession($user);

        if (!$activeSession) {
            return redirect()
                ->route('pos.session.create')
                ->withErrors(['error' => 'Buka sesi toko terlebih dahulu.']);
        }

        try {
            $this->consignmentService->addConsignment($activeSession, $validated);

            return redirect()
                ->back()
                ->with('success', 'Produk berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update sold quantity for a consignment.
     */
    public function updateSold(Request $request, DailyConsignment $consignment): RedirectResponse
    {
        $validated = $request->validate([
            'qty_sold' => 'required|integer|min:0',
        ]);

        try {
            $this->consignmentService->updateSoldQuantity(
                $consignment,
                (int) $validated['qty_sold']
            );

            return redirect()
                ->back()
                ->with('success', 'Jumlah terjual berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Bulk update sold quantities.
     */
    public function bulkUpdateSold(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:daily_consignments,id',
            'items.*.qty_sold' => 'required|integer|min:0',
        ]);

        try {
            $this->consignmentService->bulkUpdateSoldQuantities($validated['items']);

            return redirect()
                ->back()
                ->with('success', 'Semua item berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
