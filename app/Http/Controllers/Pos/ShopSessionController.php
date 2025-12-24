<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Services\ShopSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShopSessionController extends Controller
{
    public function __construct(
        protected ShopSessionService $shopSessionService
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

        return Inertia::render('Pos/OpenShop', [
            'hasActiveSession' => $hasActiveSession,
            'activeSession' => $activeSession,
            'today' => today()->format('Y-m-d'),
        ]);
    }

    /**
     * Store a new shop session.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'opening_cash' => 'required|numeric|min:0',
        ]);

        try {
            $session = $this->shopSessionService->startSession(
                $request->user(),
                (float) $validated['opening_cash']
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

        $summary = $this->shopSessionService->calculateClosingSummary($activeSession);

        return Inertia::render('Pos/CloseShop', [
            'hasSession' => true,
            'currentSession' => $activeSession,
            'consignments' => $summary['items'],
            'summary' => [
                'total_income' => $summary['total_income'],
                'total_profit' => $summary['total_profit'],
                'opening_cash' => $summary['opening_cash'],
                'expected_cash' => $summary['expected_cash'],
            ],
        ]);
    }

    /**
     * Close the shop session.
     */
    public function close(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $activeSession = $this->shopSessionService->getActiveSession($user);

        if (!$activeSession) {
            return redirect()
                ->route('pos.session.create')
                ->withErrors(['error' => 'Tidak ada sesi aktif untuk ditutup.']);
        }

        try {
            $this->shopSessionService->closeSession(
                $activeSession,
                (float) $validated['actual_cash'],
                $validated['notes'] ?? null
            );

            return redirect()
                ->route('pos.session.create')
                ->with('success', 'Sesi toko berhasil ditutup.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
