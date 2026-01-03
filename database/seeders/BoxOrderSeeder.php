<?php

namespace Database\Seeders;

use App\Models\BoxOrder;
use App\Models\BoxOrderItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BoxOrderSeeder extends Seeder
{
    /**
     * Seed box orders: 100+ orders for load testing.
     * Includes completed history + active/pending orders.
     */
    public function run(): void
    {
        $customerNames = [
            'PT Maju Jaya',
            'CV Berkah Abadi',
            'Kantor Kelurahan',
            'SD Negeri 01',
            'Bank BCA Cabang Utama',
            'Bu Rina - Arisan',
            'Pak Ahmad - Pengajian',
            'Ibu Susi - Ulang Tahun',
            'PT Teknologi Indonesia',
            'Universitas Siber',
            'Kantor BPJS',
            'Dinas Pendidikan',
            'RS Harapan Bunda',
            'Hotel Grand Inna',
            'Puskesmas Kota',
        ];

        $menuItems = [
            ['name' => 'Nasi Putih', 'price_range' => [4000, 6000]],
            ['name' => 'Ayam Goreng', 'price_range' => [12000, 18000]],
            ['name' => 'Ayam Bakar', 'price_range' => [15000, 20000]],
            ['name' => 'Rendang', 'price_range' => [18000, 25000]],
            ['name' => 'Ikan Goreng', 'price_range' => [12000, 18000]],
            ['name' => 'Sayur Lodeh', 'price_range' => [4000, 6000]],
            ['name' => 'Sayur Asem', 'price_range' => [4000, 6000]],
            ['name' => 'Kerupuk', 'price_range' => [1500, 3000]],
            ['name' => 'Risoles', 'price_range' => [2500, 4000]],
            ['name' => 'Pastel', 'price_range' => [2500, 4000]],
            ['name' => 'Lumpia', 'price_range' => [3000, 5000]],
            ['name' => 'Kroket', 'price_range' => [2500, 4000]],
            ['name' => 'Lemper', 'price_range' => [3000, 5000]],
            ['name' => 'Onde-onde', 'price_range' => [2000, 3500]],
            ['name' => 'Es Teh', 'price_range' => [2000, 4000]],
            ['name' => 'Air Mineral', 'price_range' => [3000, 5000]],
            ['name' => 'Kopi', 'price_range' => [4000, 8000]],
            ['name' => 'Jus Buah', 'price_range' => [8000, 15000]],
        ];

        // =====================================
        // Completed orders (past 30 days) - 80 orders
        // =====================================
        // =====================================
        // Historical Orders (Past 3 months)
        // =====================================
        $startDate = Carbon::now()->subMonths(3)->startOfDay();
        $endDate = Carbon::now()->subDay()->endOfDay(); // Until yesterday

        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $isWeekend = $currentDate->isWeekend();
            // Weekends: 8-15 orders, Weekdays: 3-8 orders
            $ordersCount = $isWeekend ? rand(8, 15) : rand(3, 8);

            for ($i = 0; $i < $ordersCount; $i++) {
                // Time distribution: 40% Lunch (11-13), 40% Afternoon (16-18), 20% Random
                $timeRand = rand(1, 100);
                if ($timeRand <= 40) {
                    $hour = rand(11, 13);
                } elseif ($timeRand <= 80) {
                    $hour = rand(16, 18);
                } else {
                    $hour = rand(8, 20);
                }

                $orderDateTime = $currentDate->copy()->setHour($hour)->setMinute(rand(0, 59));

                // Status distribution
                $status = rand(1, 100) <= 95 ? 'completed' : 'cancelled';

                $order = BoxOrder::create([
                    'customer_name' => $customerNames[array_rand($customerNames)],
                    'box_template_id' => null,
                    'quantity' => 1,
                    'total_price' => 0,
                    'pickup_datetime' => $orderDateTime,
                    'created_at' => $orderDateTime, // Important for daily_stats
                    'updated_at' => $orderDateTime,
                    'status' => $status,
                    'cancellation_reason' => $status === 'cancelled' ? 'Customer request' : null,
                ]);

                // Add Items
                $total = 0;
                $itemCount = rand(2, 6);
                $selectedItems = array_rand($menuItems, $itemCount);
                if (!is_array($selectedItems))
                    $selectedItems = [$selectedItems];

                foreach ($selectedItems as $itemIndex) {
                    $item = $menuItems[$itemIndex];
                    $qty = rand(15, 50);
                    $price = rand($item['price_range'][0], $item['price_range'][1]);
                    $subtotal = $qty * $price;
                    $total += $subtotal;

                    BoxOrderItem::create([
                        'box_order_id' => $order->id,
                        'product_name' => $item['name'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'subtotal' => $subtotal,
                    ]);
                }

                $order->update(['total_price' => $total]);
            }

            // Aggregate stats for this day immediately
            \Illuminate\Support\Facades\Artisan::call('stats:aggregate-daily', [
                'date' => $currentDate->toDateString()
            ]);

            $currentDate->addDay();
        }

        // =====================================
        // Active/Pending orders (future) - 25 orders
        // =====================================
        $futureOrders = [
            // Next 7 days
            ['days' => 1, 'status' => 'paid'],
            ['days' => 1, 'status' => 'pending'],
            ['days' => 2, 'status' => 'pending'],
            ['days' => 2, 'status' => 'paid'],
            ['days' => 3, 'status' => 'pending'],
            ['days' => 3, 'status' => 'paid'],
            ['days' => 4, 'status' => 'pending'],
            ['days' => 5, 'status' => 'pending'],
            ['days' => 5, 'status' => 'paid'],
            ['days' => 6, 'status' => 'pending'],
            ['days' => 7, 'status' => 'pending'],
            // 8-14 days ahead
            ['days' => 8, 'status' => 'pending'],
            ['days' => 9, 'status' => 'pending'],
            ['days' => 10, 'status' => 'pending'],
            ['days' => 11, 'status' => 'pending'],
            ['days' => 12, 'status' => 'paid'],
            ['days' => 13, 'status' => 'pending'],
            ['days' => 14, 'status' => 'pending'],
            // Far future
            ['days' => 20, 'status' => 'pending'],
            ['days' => 25, 'status' => 'pending'],
            ['days' => 30, 'status' => 'pending'],
        ];

        foreach ($futureOrders as $orderConfig) {
            $pickupDate = Carbon::now()->addDays($orderConfig['days'])->setTime(rand(9, 15), 0);

            $order = BoxOrder::create([
                'customer_name' => $customerNames[array_rand($customerNames)],
                'box_template_id' => null,
                'quantity' => 1,
                'total_price' => 0,
                'pickup_datetime' => $pickupDate,
                'status' => $orderConfig['status'],
            ]);

            // Add 2-4 items per order
            $total = 0;
            $itemCount = rand(2, 4);
            $selectedItems = array_rand($menuItems, $itemCount);

            if (!is_array($selectedItems)) {
                $selectedItems = [$selectedItems];
            }

            foreach ($selectedItems as $itemIndex) {
                $item = $menuItems[$itemIndex];
                $qty = rand(20, 100);
                $price = rand($item['price_range'][0], $item['price_range'][1]);
                $subtotal = $qty * $price;
                $total += $subtotal;

                BoxOrderItem::create([
                    'box_order_id' => $order->id,
                    'product_name' => $item['name'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ]);
            }

            $order->update(['total_price' => $total]);
        }

        $this->command->info('BoxOrderSeeder: Created ' . (80 + count($futureOrders)) . ' orders');
    }
}
