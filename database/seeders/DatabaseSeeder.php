<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Models\DailyConsignment;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Truncate Tables to start fresh
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Partner::truncate();
        ProductTemplate::truncate();
        DailyConsignment::truncate();
        // Also clear activity logs if possible, but might fail if table name differs. 
        // Better to stick to core tables.
        Schema::enableForeignKeyConstraints();

        $password = Hash::make('password');

        // 2. Create Users
        $admin = User::create([
            'name' => 'Belva Owner',
            'email' => 'admin@posita.test',
            'password' => $password,
            'role' => 'super_admin',
        ]);

        $retailer1 = User::create([
            'name' => 'Nurita Karyawan',
            'email' => 'retailer@posita.test',
            'password' => $password,
            'role' => 'employee', // Assuming 'employee' is result of 'retailer' requirement mapping
        ]);

        $retailer2 = User::create([
            'name' => 'User Karyawan',
            'email' => 'user@posita.test',
            'password' => $password,
            'role' => 'employee',
        ]);

        // 3. Create Partners
        $berkah = Partner::create([
            'name' => 'Berkah Bakery',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $snack = Partner::create([
            'name' => 'Snack Mama',
            'phone' => '089876543210',
            'is_active' => true,
        ]);

        // 4. Create Product Templates
        // Berkah Bakery Products
        ProductTemplate::create([
            'partner_id' => $berkah->id,
            'name' => 'Roti Coklat',
            'base_price' => 2000,
            'default_markup_percent' => 20, // Sell: 2400
        ]);
        ProductTemplate::create([
            'partner_id' => $berkah->id,
            'name' => 'Donat gula',
            'base_price' => 1500,
            'default_markup_percent' => 20, // Sell: 1800
        ]);

        // Snack Mama Products
        ProductTemplate::create([
            'partner_id' => $snack->id,
            'name' => 'Lemper Ayam',
            'base_price' => 2500,
            'default_markup_percent' => 10, // Sell: 2750
        ]);
        ProductTemplate::create([
            'partner_id' => $snack->id,
            'name' => 'Risoles Mayones',
            'base_price' => 3000,
            'default_markup_percent' => 15, // Sell: 3450
        ]);
        ProductTemplate::create([
            'partner_id' => $snack->id,
            'name' => 'Air Mineral',
            'base_price' => 3000,
            'default_markup_percent' => 50, // Sell: 4500
        ]);

        // 5. Create Daily Consignments (Sessions)

        // Session 1: Closed Yesterday (Retailer 1)
        DailyConsignment::create([
            'date' => Carbon::yesterday(),
            'partner_id' => $berkah->id,
            'product_name' => 'Roti Coklat',
            'initial_stock' => 50,
            'base_price' => 2000,
            'markup_percentage' => 20,
            'selling_price' => 2400,
            'quantity_sold' => 45,
            'total_revenue' => 108000,
            'total_profit' => 18000,
            'status' => 'closed',
            'input_by_user_id' => $retailer1->id,
        ]);

        // Session 2: Active Today (Retailer 1)
        DailyConsignment::create([
            'date' => Carbon::today(),
            'partner_id' => $snack->id,
            'product_name' => 'Lemper Ayam',
            'initial_stock' => 30,
            'base_price' => 2500,
            'markup_percentage' => 10,
            'selling_price' => 2750,
            'quantity_sold' => 0,
            'status' => 'open',
            'input_by_user_id' => $retailer1->id,
        ]);
    }
}
