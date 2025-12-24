<?php

namespace App\Actions\Consignment;

use App\Models\DailyConsignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class StartDailyShopAction
{

    public function execute(User $user, array $data): DailyConsignment
    {

        if ($data['initial_stock'] <= 0) {
            throw new Exception("Stok awal harus lebih besar dari 0.");
        }

        $allowedMarkup = [5, 10, 15];
        if (!in_array((int)$data['markup_percentage'], $allowedMarkup)) {
            throw new Exception("Opsi markup harus 5%, 10%, atau 15%.");
        }

        return DB::transaction(function () use ($user, $data) {
            $basePrice = (float) $data['base_price'];
            $markupPercent = (int) $data['markup_percentage']; 
            
            // Rumus: Harga Jual = Harga Dasar + (Harga Dasar * % Markup)
            $sellingPrice = $basePrice + ($basePrice * ($markupPercent / 100));

            return DailyConsignment::create([
                'shop_session_id'   => $data['shop_session_id'] ?? null,
                'date'              => now()->toDateString(),
                'partner_id'        => $data['partner_id'] ?? null,
                'manual_partner_name' => $data['manual_partner_name'] ?? null,
                'product_name'      => $data['product_name'],
                'initial_stock'     => (int) $data['initial_stock'],
                'remaining_stock'   => (int) $data['initial_stock'],
                'base_price'        => $basePrice,
                'markup_percentage' => $markupPercent,
                'selling_price'     => $sellingPrice,
                'status'            => 'open',
                'input_by_user_id'  => $user->id,
            ]);
        });
    }
}