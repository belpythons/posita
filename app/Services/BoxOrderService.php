<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\BoxOrderItem;
use App\Models\BoxTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BoxOrderService
{
    /**
     * Create a new box order with flexible line items.
     */
    public function createOrder(array $data): BoxOrder
    {
        return DB::transaction(function () use ($data) {
            $boxQuantity = $data['quantity'] ?? 1;
            $itemsSubtotal = 0;

            // Calculate subtotal from items (per box)
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemsSubtotal += $item['quantity'] * $item['unit_price'];
                }
            } elseif (!empty($data['box_template_id'])) {
                // Fallback to template-based pricing
                $template = BoxTemplate::findOrFail($data['box_template_id']);
                if (!$template->is_active) {
                    throw new \Exception('Template box ini sudah tidak aktif.');
                }
                $itemsSubtotal = $template->price;
            }

            // Total price = items subtotal × box quantity
            $totalPrice = $itemsSubtotal * $boxQuantity;

            $order = BoxOrder::create([
                'customer_name' => $data['customer_name'],
                'box_template_id' => $data['box_template_id'] ?? null,
                'quantity' => $boxQuantity,
                'total_price' => $data['total_price'] ?? $totalPrice,
                'pickup_datetime' => $data['pickup_datetime'],
                'status' => 'pending',
            ]);

            // Create line items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $subtotal = $itemData['quantity'] * $itemData['unit_price'];
                    $order->items()->create([
                        'product_name' => $itemData['product_name'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            return $order->load('items');
        });
    }

    /**
     * Upload payment proof for an order.
     */
    public function uploadPaymentProof(BoxOrder $order, UploadedFile $file): BoxOrder
    {
        // Delete old proof if exists
        if ($order->payment_proof_path) {
            Storage::disk('public')->delete($order->payment_proof_path);
        }

        $path = $file->store('payment-proofs', 'public');

        $order->update([
            'payment_proof_path' => $path,
            'status' => 'paid',
        ]);

        return $order->fresh();
    }

    /**
     * Get orders with optional filters.
     */
    public function getOrders(array $filters = []): Collection
    {
        $query = BoxOrder::with(['template', 'items']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $query->whereDate('pickup_datetime', $filters['date']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('pickup_datetime', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('pickup_datetime', '<=', $filters['to_date']);
        }

        return $query->orderBy('pickup_datetime', 'asc')->get();
    }

    /**
     * Get pending orders.
     */
    public function getPendingOrders(): Collection
    {
        return BoxOrder::pending()
            ->with(['template', 'items'])
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Get today's orders.
     */
    public function getTodayOrders(): Collection
    {
        return BoxOrder::today()
            ->with(['template', 'items'])
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Get upcoming orders (next 7 days).
     */
    public function getUpcomingOrders(): Collection
    {
        return BoxOrder::whereBetween('pickup_datetime', [now(), now()->addDays(7)])
            ->with(['template', 'items'])
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(BoxOrder $order, string $status): BoxOrder
    {
        $validStatuses = ['pending', 'paid', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Status tidak valid.');
        }

        $order->update(['status' => $status]);

        return $order->fresh();
    }

    /**
     * Cancel an order with a reason.
     */
    public function cancelOrderWithReason(BoxOrder $order, string $reason): BoxOrder
    {
        if ($order->isCompleted()) {
            throw new \Exception('Order yang sudah selesai tidak dapat dibatalkan.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        return $order->fresh();
    }

    /**
     * Cancel an order (legacy, without reason).
     */
    public function cancelOrder(BoxOrder $order): BoxOrder
    {
        if ($order->isCompleted()) {
            throw new \Exception('Order yang sudah selesai tidak dapat dibatalkan.');
        }

        return $this->updateOrderStatus($order, 'cancelled');
    }

    /**
     * Complete an order.
     */
    public function completeOrder(BoxOrder $order): BoxOrder
    {
        if (!$order->isPaid()) {
            throw new \Exception('Order harus sudah dibayar sebelum diselesaikan.');
        }

        return $this->updateOrderStatus($order, 'completed');
    }

    /**
     * Generate receipt PDF for an order.
     */
    public function generateReceipt(BoxOrder $order)
    {
        $order->load(['template', 'items']);

        $data = [
            'order' => $order,
            'generated_at' => now(),
        ];

        return Pdf::loadView('reports.box-receipt', $data)
            ->stream('kwitansi-' . $order->id . '.pdf');
    }

    /**
     * Get order statistics.
     */
    public function getOrderStatistics(array $filters = []): array
    {
        $orders = $this->getOrders($filters);

        return [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'paid_orders' => $orders->where('status', 'paid')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'total_revenue' => $orders->whereIn('status', ['paid', 'completed'])->sum('total_price'),
        ];
    }
}
