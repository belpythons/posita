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
    /**
     * Dependency Injection melalui Constructor.
     */
    public function __construct(
        protected BoxOrderService $boxOrderService,
        protected AdminDataService $adminDataService
    ) {
    }

    /**
     * Menampilkan daftar pesanan box (Dashboard).
     */
    public function index(): Response
    {
        // Mengambil data dari Service
        $upcomingOrders = $this->boxOrderService->getUpcomingOrders();
        $todayOrders = $this->boxOrderService->getTodayOrders();

        return Inertia::render('Pos/Box/Index', [
            'upcomingOrders' => $upcomingOrders,
            'todayOrders' => $todayOrders,
        ]);
    }

    /**
     * Menampilkan form buat order baru.
     */
    public function create(?BoxTemplate $template = null): Response
    {
        $boxTemplates = $this->adminDataService->getBoxTemplates();

        return Inertia::render('Pos/Box/Create', [
            'selectedTemplate' => $template,
            'boxTemplates' => $boxTemplates,
        ]);
    }

    /**
     * Menyimpan order box baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name'     => 'required|string|max:255',
            'box_template_id'   => 'nullable|exists:box_templates,id',
            'pickup_datetime'   => 'required|date|after:now',
            'quantity'          => 'required|integer|min:1',
            'items'             => 'required|array|min:1',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
        ]);

        try {
            $order = $this->boxOrderService->createOrder($validated);

            return redirect()
                ->route('pos.box.index')
                ->with('success', 'Order berhasil dibuat untuk ' . $order->customer_name);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'Gagal membuat order: ' . $e->getMessage()]);
        }
    }

    /**
     * Update status order (Digunakan oleh modal di Index.vue).
     */
    public function updateStatus(Request $request, int $orderId): RedirectResponse
    {
        $order = BoxOrder::findOrFail($orderId);
        $targetStatus = $request->input('status');

        $rules = [
            'status' => 'required|in:pending,paid,completed,cancelled',
        ];

        // Validasi tambahan jika status dibatalkan
        if ($targetStatus === 'cancelled') {
            $rules['cancellation_reason'] = 'required|string|min:5|max:1000';
        }

        $validated = $request->validate($rules);

        try {
            if ($targetStatus === 'cancelled') {
                $this->boxOrderService->cancelOrderWithReason($order, $validated['cancellation_reason']);
            } else {
                $this->boxOrderService->updateOrderStatus($order, $validated['status']);
            }

            return redirect()->back()->with('success', 'Status berhasil diubah menjadi ' . $targetStatus);
                
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['error' => 'Gagal update status: ' . $e->getMessage()]);
        }
    }

    /**
     * Download struk/receipt PDF.
     */
    public function downloadReceipt(int $id)
    {
        // Cari data manual untuk menghindari error Route Model Binding
        $order = BoxOrder::findOrFail($id);
        return $this->boxOrderService->generateReceipt($order);
    }

    /**
     * Method upload bukti bayar (Jika diperlukan fitur upload manual).
     */
    public function uploadProof(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $order = BoxOrder::findOrFail($orderId);

        try {
            $this->boxOrderService->uploadPaymentProof($order, $request->file('payment_proof'));
            return redirect()->back()->with('success', 'Bukti pembayaran berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Gagal upload: ' . $e->getMessage()]);
        }
    }
}