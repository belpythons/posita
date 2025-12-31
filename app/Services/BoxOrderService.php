<?php

namespace App\Services;

use App\Models\BoxOrder;
use App\Models\BoxTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BoxOrderService
{
    /**
     * Membuat pesanan box baru dengan item fleksibel.
     */
    public function createOrder(array $data): BoxOrder
    {
        return DB::transaction(function () use ($data) {
            $boxQuantity = $data['quantity'] ?? 1;
            $itemsSubtotal = 0;

            // Hitung total harga dasar
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $itemsSubtotal += $item['quantity'] * $item['unit_price'];
                }
            } elseif (!empty($data['box_template_id'])) {
                $template = BoxTemplate::findOrFail($data['box_template_id']);
                $itemsSubtotal = $template->price;
            }

            // Simpan Header Order
            $order = BoxOrder::create([
                'customer_name'   => $data['customer_name'],
                'box_template_id' => $data['box_template_id'] ?? null,
                'quantity'        => $boxQuantity,
                'total_price'     => $data['total_price'] ?? ($itemsSubtotal * $boxQuantity),
                'pickup_datetime' => $data['pickup_datetime'],
                'status'          => 'pending',
            ]);

            // Simpan Detail Items
            if (!empty($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $order->items()->create([
                        'product_name' => $itemData['product_name'],
                        'quantity'     => $itemData['quantity'],
                        'unit_price'   => $itemData['unit_price'],
                    ]);
                }
            }

            return $order->load('items');
        });
    }

    /**
     * Mengambil pesanan untuk hari ini ATAU yang sudah siap (Paid/Completed).
     */
    public function getTodayOrders(): Collection
    {
        return BoxOrder::where(function($query) {
                $query->whereDate('pickup_datetime', now()->toDateString())
                      ->orWhereIn('status', ['paid', 'completed']);
            })
            ->where('status', '!=', 'cancelled')
            ->with(['template', 'items'])
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Mengambil pesanan mendatang yang MASIH PENDING.
     */
    public function getUpcomingOrders(): Collection
    {
        return BoxOrder::where('pickup_datetime', '>', now())
            ->where('status', 'pending')
            ->with(['template', 'items'])
            ->orderBy('pickup_datetime', 'asc')
            ->get();
    }

    /**
     * Update status order secara umum.
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
     * Batalkan order dengan memberikan alasan pembatalan.
     * (INI FUNGSI YANG SEBELUMNYA HILANG/ERROR)
     */
    public function cancelOrderWithReason(BoxOrder $order, string $reason): BoxOrder
    {
        if ($order->status === 'completed') {
            throw new \Exception('Order yang sudah selesai tidak dapat dibatalkan.');
        }

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        return $order->fresh();
    }

    /**
     * Upload bukti pembayaran dan update status menjadi paid (Lunas).
     */
    public function uploadPaymentProof(BoxOrder $order, UploadedFile $file): BoxOrder
    {
        return DB::transaction(function () use ($order, $file) {
            // Hapus file lama jika ada
            if ($order->payment_proof && Storage::disk('public')->exists($order->payment_proof)) {
                Storage::disk('public')->delete($order->payment_proof);
            }

            // Simpan file baru
            $fileName = 'proof_' . $order->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('box_payments', $fileName, 'public');

            $order->update([
                'payment_proof' => $path,
                'status' => 'paid'
            ]);

            return $order->fresh();
        });
    }

    /**
     * Generate PDF Receipt.
     */
    public function generateReceipt(BoxOrder $order)
    {
        $order->load(['template', 'items']);
        return Pdf::loadView('reports.box-receipt', [
            'order' => $order, 
            'generated_at' => now()
        ])->stream('kwitansi-' . $order->id . '.pdf');
    }
}