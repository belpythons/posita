<?php

namespace Database\Seeders;

use App\Models\BoxOrder;
use App\Models\BoxOrderItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TodayBoxOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create('id_ID');

        // Define some realistic snack/box item names
        $snackItems = [
            'Lemper Ayam' => 2500,
            'Risol Mayo' => 3000,
            'Pastel Tutup' => 3500,
            'Sosis Solo' => 2500,
            'Pie Buah' => 4000,
            'Kue Sus' => 3000,
            'Bolu Kukus' => 2000,
            'Martabak Mini' => 2500,
            'Tahu Bakso' => 1500,
            'Arem-arem' => 2000
        ];

        // Generate 8 orders for today
        for ($i = 0; $i < 8; $i++) {
            // Random time between 08:00 and 17:00 today
            $pickupTime = Carbon::now()->startOfDay()->addHours(rand(8, 16))->addMinutes(rand(0, 59));

            // 70% chance of pending/paid, 30% already completed if time is past
            $status = 'pending';
            if ($pickupTime->isPast()) {
                $status = $faker->randomElement(['completed', 'pending', 'paid', 'cancelled']);
            } else {
                $status = $faker->randomElement(['pending', 'paid']);
            }

            $order = BoxOrder::create([
                'customer_name' => $faker->name,
                'pickup_datetime' => $pickupTime,
                'status' => $status,
                'quantity' => rand(10, 100), // Total boxes ordered
                'total_price' => 0, // Will recalculate
                'cancellation_reason' => $status === 'cancelled' ? 'Dibatalkan oleh pelanggan' : null,
            ]);

            // Add 3-5 items per order
            $orderTotal = 0;
            $numItems = rand(3, 5);
            $selectedSnacks = $faker->randomElements(array_keys($snackItems), $numItems);

            foreach ($selectedSnacks as $snackName) {
                $qty = $order->quantity; // Typically same qty as box count if it's "isi box", or multiplier
                $price = $snackItems[$snackName];
                $subtotal = $qty * $price;

                BoxOrderItem::create([
                    'box_order_id' => $order->id,
                    'product_name' => $snackName,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $orderTotal += $subtotal;
            }

            $order->update(['total_price' => $orderTotal]);
        }


        // Generate 5 UPCOMING orders (tomorrow + 1-3 days)
        for ($i = 0; $i < 5; $i++) {
            $pickupTime = Carbon::now()->addDays(rand(1, 3))->setHour(rand(8, 16))->setMinute(rand(0, 59));

            $status = 'pending';

            $order = BoxOrder::create([
                'customer_name' => $faker->name . ' (UPCOMING)',
                'pickup_datetime' => $pickupTime,
                'status' => $status,
                'quantity' => rand(5, 50),
                'total_price' => 0,
            ]);

            // Add items
            $orderTotal = 0;
            $numItems = rand(2, 4);
            $selectedSnacks = $faker->randomElements(array_keys($snackItems), $numItems);

            foreach ($selectedSnacks as $snackName) {
                $qty = $order->quantity;
                $price = $snackItems[$snackName];
                $subtotal = $qty * $price;

                BoxOrderItem::create([
                    'box_order_id' => $order->id,
                    'product_name' => $snackName,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                ]);

                $orderTotal += $subtotal;
            }

            $order->update(['total_price' => $orderTotal]);
        }
    }
}
