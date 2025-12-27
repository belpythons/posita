<?php

namespace App\Actions;

use App\Models\ShopSession;
use App\Models\DailyConsignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Nurita Wahuyuni | 202312061

class StartDailyShopAction
{
    public function execute(User $user, float $openingCash, array $consignments): ShopSession
    {
        return DB::transaction(function () use ($user, $openingCash, $consignments) {
            // 1️⃣ Create new shop session
            $session = ShopSession::create([
                'user_id' => $user->id,
                'opened_at' => Carbon::now(),
                'opening_cash' => $openingCash,
                'status' => 'open',
            ]);

            // 2️⃣ Insert daily consignments
            foreach ($consignments as $item) {
                $template = \App\Models\ProductTemplate::findOrFail($item['product_template_id']);

                DailyConsignment::create([
                    'shop_session_id' => $session->id,
                    'partner_id' => $item['partner_id'],
                    'product_name' => $template->name,
                    'qty_initial' => 0, // nanti bisa diinput stok awal
                    'qty_sold' => 0,
                    'qty_remaining' => 0,
                    'base_price' => $template->base_price,
                    'selling_price' => $template->default_selling_price ?? $template->base_price,
                    'markup_percent' => 10,
                    'subtotal_income' => 0,
                ]);
            }

            return $session;
        });
    }
}
