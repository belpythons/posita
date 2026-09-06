<?php

namespace Database\Seeders;

use App\Models\BoxOrder;
use App\Models\BoxTemplate;
use App\Models\DailyConsignment;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Models\ShopSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * A small, self-contained dataset for the second tenant.
 *
 * Its only job is to prove isolation: distinct users, partners, templates,
 * sessions and orders that must never show up under the first tenant. It is
 * intentionally far smaller than the demo data of the main tenant.
 */
class SecondTenantSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->seedUsers();
        $partners = $this->seedPartners();
        $templates = $this->seedBoxTemplates();

        $this->seedOpenSession($users['cashier'], $partners);
        $this->seedBoxOrders($templates);
    }

    /**
     * @return array{admin: User, cashier: User}
     */
    private function seedUsers(): array
    {
        return [
            'admin' => User::create([
                'name' => 'Senja Admin',
                'email' => 'admin@kopisenja.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]),
            'cashier' => User::create([
                'name' => 'Senja Kasir',
                'email' => 'kasir@kopisenja.com',
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ]),
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, Partner>
     */
    private function seedPartners()
    {
        return collect([
            ['name' => 'Roastery Senja', 'products' => [
                ['name' => 'Biji Arabika 250g', 'base' => 55000, 'sell' => 75000],
                ['name' => 'Biji Robusta 250g', 'base' => 40000, 'sell' => 55000],
            ]],
            ['name' => 'Dapur Senja', 'products' => [
                ['name' => 'Croissant', 'base' => 12000, 'sell' => 18000],
                ['name' => 'Banana Bread', 'base' => 10000, 'sell' => 16000],
            ]],
        ])->map(function (array $data) {
            $partner = Partner::create([
                'name' => $data['name'],
                'phone' => '0812'.random_int(10000000, 99999999),
                'address' => 'Jl. Senja No. '.random_int(1, 99),
                'is_active' => true,
            ]);

            foreach ($data['products'] as $product) {
                ProductTemplate::create([
                    'partner_id' => $partner->id,
                    'name' => $product['name'],
                    'base_price' => $product['base'],
                    'default_selling_price' => $product['sell'],
                    'is_active' => true,
                ]);
            }

            return $partner->load('productTemplates');
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, BoxTemplate>
     */
    private function seedBoxTemplates()
    {
        return collect([
            ['name' => 'Senja Coffee Box', 'type' => 'snack_box', 'price' => 45000,
                'items' => ['Kopi Susu', 'Croissant', 'Air Mineral']],
            ['name' => 'Senja Lunch Box', 'type' => 'heavy_meal', 'price' => 38000,
                'items' => ['Nasi Ayam', 'Sayur', 'Buah']],
        ])->map(fn (array $data) => BoxTemplate::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'price' => $data['price'],
            'items_json' => $data['items'],
            'is_active' => true,
        ]));
    }

    private function seedOpenSession(User $cashier, $partners): void
    {
        $session = ShopSession::create([
            'user_id' => $cashier->id,
            'opened_at' => Carbon::today()->setHour(7),
            'opening_cash' => 250000,
            'status' => 'open',
        ]);

        foreach ($partners as $partner) {
            foreach ($partner->productTemplates as $product) {
                $qty = random_int(5, 15);

                DailyConsignment::create([
                    'shop_session_id' => $session->id,
                    'partner_id' => $partner->id,
                    'product_name' => $product->name,
                    'qty_initial' => $qty,
                    'qty_sold' => 0,
                    'qty_remaining' => $qty,
                    'base_price' => $product->base_price,
                    'selling_price' => $product->default_selling_price,
                    'markup_percent' => 0,
                    'subtotal_income' => 0,
                ]);
            }
        }
    }

    private function seedBoxOrders($templates): void
    {
        $customers = ['Rani', 'Dimas', 'Putri', 'Yoga', 'Sinta', 'Bagas'];
        $dates = [];

        foreach (range(1, 12) as $i) {
            $template = $templates->random();
            $quantity = random_int(2, 8);
            $createdAt = Carbon::today()->subDays(random_int(1, 10))->setHour(random_int(9, 17));
            $dates[$createdAt->toDateString()] = true;

            $order = BoxOrder::create([
                'customer_name' => $customers[array_rand($customers)],
                'box_template_id' => $template->id,
                'quantity' => $quantity,
                'total_price' => $template->price * $quantity,
                'pickup_datetime' => $createdAt->copy()->addDay(),
                'status' => 'completed',
            ]);

            $order->items()->create([
                'product_name' => $template->name,
                'quantity' => $quantity,
                'unit_price' => $template->price,
                'subtotal' => $template->price * $quantity,
            ]);

            // created_at is not fillable, so passing it to create() would be
            // silently discarded and every order would land on today — which
            // the daily aggregation below would then read as zero.
            $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
        }

        // The main tenant's BoxOrderSeeder aggregates as it goes; this tenant is
        // seeded afterwards, so its days need aggregating here or its dashboard
        // history stays empty.
        foreach (array_keys($dates) as $date) {
            Artisan::call('stats:aggregate-daily', ['date' => $date]);
        }
    }
}
