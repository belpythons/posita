<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\ShopSession;
use App\Models\Partner;
use App\Services\ConsignmentService;
use App\Services\ReportService;
use App\Services\ShopSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopSessionController extends Controller
{
    public function __construct(
        protected ShopSessionService $shopSessionService,
        protected ConsignmentService $consignmentService,
        protected ReportService $reportService
    ) {
    }

    /**
     * Show form to open a new shop session.
     */
    public function create(): Response
    {
        $user = auth()->user();
        $hasActiveSession = $this->shopSessionService->hasActiveSession($user);
        $activeSession = $this->shopSessionService->getActiveSession($user);

        // Get last closed session for report download
        $lastClosedSession = ShopSession::where('user_id', $user->id)
            ->where('status', 'closed')
            ->latest('closed_at')
            ->first();
        
        // Get active partners and their product templates
        $partners = Partner::with(['productTemplates' => function ($q) {
            $q->where('is_active', true);
        }])->where('is_active', true)->get();

        return Inertia::render('Pos/OpenShop', [
            'hasActiveSession' => $hasActiveSession,
            'activeSession' => $activeSession,
            'lastClosedSession' => $lastClosedSession,
            'today' => today()->format('Y-m-d'),
            'partners' => $partners,
        ]);
    }

    /**
     * Store a new shop session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'consignments' => 'required|array|min:1',
            'consignments.*.partner_id' => 'required|exists:partners,id',
            'consignments.*.product_template_id' => 'required|exists:product_templates,id',
        ]);

        try {
            $session = $this->shopSessionService->startSession(
                $request->user(),
                (float) $validated['opening_cash'],
                $validated['consignments']
            );

            return redirect()
                ->route('pos.consignment.index')
                ->with('success', 'Sesi toko berhasil dibuka.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show form to close the shop session.
     */
    public function showClose(): Response
    {
        $user = auth()->user();
        $activeSession = $this->shopSessionService->getActiveSession($user);

        if (!$activeSession) {
            return Inertia::render('Pos/CloseShop', [
                'hasSession' => false,
                'currentSession' => null,
                'consignments' => [],
                'summary' => null,
            ]);
        }

        // Load consignments with partner
        $activeSession->load('consignments.partner');

        $consignments = $activeSession->consignments->map(function ($item) {
            return [
                'id' => $item->id,
                'product_name' => $item->product_name,
                'partner' => $item->partner,
                'qty_initial' => $item->qty_initial,
                'qty_remaining' => $item->qty_remaining,
                'base_price' => (float) $item->base_price,
                'selling_price' => (float) $item->selling_price,
            ];
        });

        return Inertia::render('Pos/CloseShop', [
            'hasSession' => true,
            'currentSession' => $activeSession,
            'consignments' => $consignments,
            'summary' => [
                'opening_cash' => $activeSession->opening_cash,
            ],
        ]);
    }

    /**
     * Close the shop session with leftover quantities.
     */
    public function close(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'leftovers' => 'required|array',
            'leftovers.*.id' => 'required|exists:daily_consignments,id',
            'leftovers.*.qty_remaining' => 'required|integer|min:0',
        ]);

        $user = auth()->user();
        $activeSession = $this->shopSessionService->getActiveSession($user);

        if (!$activeSession) {
            return redirect()
                ->route('pos.session.create')
                ->withErrors(['error' => 'Tidak ada sesi aktif untuk ditutup.']);
        }

        try {
            // First, update all consignment remaining quantities
            $this->consignmentService->bulkUpdateRemainingQuantities($validated['leftovers']);

            // Then close the session
            $this->shopSessionService->closeSession(
                $activeSession,
                (float) $validated['actual_cash'],
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('pos.session.create')
                ->with('success', 'Sesi toko berhasil ditutup.')
                ->with('lastSessionId', $activeSession->id);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download PDF report for a session.
     */
    public function downloadReport(ShopSession $session)
    {
        // Verify ownership or allow if admin
        $user = auth()->user();
        if ($session->user_id !== $user->id && $user->role !== 'admin') {
            abort(403, 'Anda tidak memiliki akses ke laporan ini.');
        }

        // Only allow download for closed sessions
        if ($session->status !== 'closed') {
            abort(403, 'Laporan hanya tersedia setelah sesi ditutup.');
        }

        return $this->reportService->streamSessionReport($session);
    }
}
