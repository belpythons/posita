<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\BoxOrder;
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
     * Display box order listing with countdown timers.
     */
    public function index(): Response
    {
        $upcomingOrders = $this->boxOrderService->getUpcomingOrders();
        $todayOrders = $this->boxOrderService->getTodayOrders();

        // Add time remaining to each order
        $upcomingOrders = $upcomingOrders->map(function ($order) {
            $order->time_remaining = $order->getTimeRemainingAttribute();
            return $order;
        });

        return Inertia::render('Pos/Box/Index', [
            'upcomingOrders' => $upcomingOrders,
            'todayOrders' => $todayOrders,
        ]);
    }

    /**
     * Show form to create a new order (flexible line items).
     */
    public function create(BoxTemplate $template = null): Response
    {
        // Get all active box templates for dropdown
        $boxTemplates = $this->adminDataService->getBoxTemplates();

        return Inertia::render('Pos/Box/Create', [
            'selectedTemplate' => $template,
            'boxTemplates' => $boxTemplates,
        ]);
    }

    /**
     * Store a new box order with flexible line items.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'pickup_datetime' => 'required|date|after:now',
            'quantity' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
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

        $order = BoxOrder::findOrFail($orderId);

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
     * Update order status with conditional validation.
     * - For paid/completed: requires payment_proof upload
     * - For cancelled: requires cancellation_reason
     */
    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $rules = [
            'status' => 'required|in:pending,paid,completed,cancelled',
        ];

        // Conditional validation based on target status
        $targetStatus = $request->input('status');

        if (in_array($targetStatus, ['paid', 'completed'])) {
            // If order doesn't have payment proof yet, require it
            $order = BoxOrder::findOrFail($orderId);
            if (!$order->payment_proof_path) {
                $rules['payment_proof'] = 'required|image|max:5120';
            }
        }

        if ($targetStatus === 'cancelled') {
            $rules['cancellation_reason'] = 'required|string|max:1000';
        }

        $validated = $request->validate($rules);

        $order = BoxOrder::findOrFail($orderId);

        try {
            // Handle payment proof upload if provided
            if ($request->hasFile('payment_proof')) {
                $this->boxOrderService->uploadPaymentProof($order, $request->file('payment_proof'));
                $order->refresh();
            }

            // Handle cancellation reason
            if ($targetStatus === 'cancelled' && !empty($validated['cancellation_reason'])) {
                $this->boxOrderService->cancelOrderWithReason($order, $validated['cancellation_reason']);
            } else {
                $this->boxOrderService->updateOrderStatus($order, $validated['status']);
            }

            return redirect()
                ->back()
                ->with('success', 'Status order berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Download receipt PDF for an order.
     */
    public function downloadReceipt(BoxOrder $order)
    {
        return $this->boxOrderService->generateReceipt($order);
    }
}
