<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\BoxTemplate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BoxOrderService
{
    /**
     * Create a new box order.
     */
    public function createOrder(array $data): BoxOrder
    {
        $template = BoxTemplate::findOrFail($data['box_template_id']);

        if (!$template->is_active) {
            throw new \Exception('Template box ini sudah tidak aktif.');
        }

        $quantity = (int) $data['quantity'];
        $totalPrice = $template->price * $quantity;

        return BoxOrder::create([
            'customer_name' => $data['customer_name'],
            'box_template_id' => $template->id,
            'quantity' => $quantity,
            'total_price' => $data['total_price'] ?? $totalPrice,
            'pickup_datetime' => $data['pickup_datetime'],
            'status' => 'pending',
        ]);
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
        $query = BoxOrder::with('template');

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
            ->with('template')
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Get today's orders.
     */
    public function getTodayOrders(): Collection
    {
        return BoxOrder::today()
            ->with('template')
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
     * Cancel an order.
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
