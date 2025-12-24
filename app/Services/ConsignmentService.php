<?php

namespace App\Services;

use App\Models\DailyConsignment;
use App\Models\ShopSession;
use Illuminate\Database\Eloquent\Collection;

class ConsignmentService
{
    /**
     * Calculate selling price based on markup percentage.
     */
    public function calculateSellingPrice(float $basePrice, int $markupPercent): float
    {
        return $basePrice * (1 + ($markupPercent / 100));
    }

    /**
     * Add a new consignment item to a session.
     */
    public function addConsignment(ShopSession $session, array $data): DailyConsignment
    {
        if ($session->isClosed()) {
            throw new \Exception('Sesi sudah ditutup. Tidak dapat menambah item.');
        }

        $basePrice = (float) $data['base_price'];
        $markupPercent = (int) ($data['markup_percent'] ?? 10);
        $sellingPrice = $data['selling_price'] ?? $this->calculateSellingPrice($basePrice, $markupPercent);
        $qtyInitial = (int) $data['qty_initial'];

        return DailyConsignment::create([
            'shop_session_id' => $session->id,
            'partner_id' => $data['partner_id'],
            'product_name' => $data['product_name'],
            'qty_initial' => $qtyInitial,
            'qty_sold' => 0,
            'qty_remaining' => $qtyInitial,
            'base_price' => $basePrice,
            'selling_price' => $sellingPrice,
            'markup_percent' => $markupPercent,
            'subtotal_income' => 0,
        ]);
    }

    /**
     * Get consignments for a session.
     */
    public function getSessionConsignments(ShopSession $session): Collection
    {
        return $session->consignments()
            ->with('partner')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update sold quantity for a consignment item.
     */
    public function updateSoldQuantity(DailyConsignment $consignment, int $qtySold): DailyConsignment
    {
        // Validate qty_sold doesn't exceed qty_initial
        if ($qtySold > $consignment->qty_initial) {
            throw new \Exception('Jumlah terjual tidak boleh melebihi stok awal.');
        }

        if ($qtySold < 0) {
            throw new \Exception('Jumlah terjual tidak boleh negatif.');
        }

        $consignment->update([
            'qty_sold' => $qtySold,
            'qty_remaining' => $consignment->qty_initial - $qtySold,
            'subtotal_income' => $qtySold * $consignment->selling_price,
        ]);

        return $consignment->fresh();
    }

    /**
     * Bulk update sold quantities.
     */
    public function bulkUpdateSoldQuantities(array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $consignment = DailyConsignment::find($item['id']);
            if ($consignment) {
                $results[] = $this->updateSoldQuantity($consignment, (int) $item['qty_sold']);
            }
        }

        return $results;
    }

    /**
     * Get consignment summary for a session.
     */
    public function getSessionSummary(ShopSession $session): array
    {
        $consignments = $session->consignments;

        return [
            'total_items' => $consignments->count(),
            'total_qty_initial' => $consignments->sum('qty_initial'),
            'total_qty_sold' => $consignments->sum('qty_sold'),
            'total_qty_remaining' => $consignments->sum('qty_remaining'),
            'total_income' => $consignments->sum('subtotal_income'),
            'total_profit' => $consignments->sum(function ($c) {
                return $c->qty_sold * ($c->selling_price - $c->base_price);
            }),
        ];
    }
}
