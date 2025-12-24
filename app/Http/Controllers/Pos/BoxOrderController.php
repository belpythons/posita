<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BoxTemplate;
use App\Services\AdminDataService;
use App\Services\BoxOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BoxOrderController extends Controller
{
    public function __construct(
        protected BoxOrderService $boxOrderService,
        protected AdminDataService $adminDataService
    ) {
    }

    /**
     * Display box order selection page.
     */
    public function index(): Response
    {
        $templates = $this->adminDataService->getBoxTemplates();

        // Separate by type
        $heavyMealTemplates = $templates->where('type', 'heavy_meal')->where('is_active', true)->values();
        $snackBoxTemplates = $templates->where('type', 'snack_box')->where('is_active', true)->values();

        // Get today's orders
        $todayOrders = $this->boxOrderService->getTodayOrders();

        return Inertia::render('Pos/Box/Index', [
            'heavyMealTemplates' => $heavyMealTemplates,
            'snackBoxTemplates' => $snackBoxTemplates,
            'todayOrders' => $todayOrders,
        ]);
    }

    /**
     * Show form to create a new order for a template.
     */
    public function create(BoxTemplate $template): Response
    {
        if (!$template->is_active) {
            abort(404, 'Template tidak aktif.');
        }

        return Inertia::render('Pos/Box/Create', [
            'selectedTemplate' => $template,
        ]);
    }

    /**
     * Store a new box order.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'box_template_id' => 'required|exists:box_templates,id',
            'customer_name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'pickup_datetime' => 'required|date|after:now',
        ]);

        try {
            $order = $this->boxOrderService->createOrder($validated);

            return redirect()
                ->route('pos.box.index')
                ->with('success', 'Order berhasil dibuat untuk ' . $order->customer_name);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Upload payment proof for an order.
     */
    public function uploadProof(Request $request, int $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'payment_proof' => 'required|image|max:5120', // 5MB max
        ]);

        $order = \App\Models\BoxOrder::findOrFail($orderId);

        try {
            $this->boxOrderService->uploadPaymentProof($order, $request->file('payment_proof'));

            return redirect()
                ->back()
                ->with('success', 'Bukti pembayaran berhasil diupload.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,paid,completed,cancelled',
        ]);

        $order = \App\Models\BoxOrder::findOrFail($orderId);

        try {
            $this->boxOrderService->updateOrderStatus($order, $validated['status']);

            return redirect()
                ->back()
                ->with('success', 'Status order berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
