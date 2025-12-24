<?php

namespace Database\Seeders;

use App\Models\BoxTemplate;
use App\Models\Partner;
use App\Models\ProductTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin Posita',
            'email' => 'admin@posita.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create Employee Users
        $employees = [
            ['name' => 'Rivaldi', 'email' => 'rivaldi@posita.com'],
            ['name' => 'Amar', 'email' => 'amar@posita.com'],
            ['name' => 'Nurita', 'email' => 'nurita@posita.com'],
        ];

        foreach ($employees as $employee) {
            User::create([
                'name' => $employee['name'],
                'email' => $employee['email'],
                'password' => Hash::make('password'),
                'role' => 'employee',
                'is_active' => true,
            ]);
        }

        // Create Partners (Penyetok)
        $partners = [
            [
                'name' => 'Bu Siti - Gorengan',
                'phone' => '081234567890',
                'address' => 'Jl. Pasar No. 10',
            ],
            [
                'name' => 'Pak Budi - Kue Basah',
                'phone' => '081234567891',
                'address' => 'Jl. Merdeka No. 5',
            ],
            [
                'name' => 'Mbak Ani - Roti',
                'phone' => '081234567892',
                'address' => 'Jl. Kenanga No. 15',
            ],
        ];

        foreach ($partners as $partnerData) {
            $partner = Partner::create([
                'name' => $partnerData['name'],
                'phone' => $partnerData['phone'],
                'address' => $partnerData['address'],
                'is_active' => true,
            ]);

            // Create ProductTemplates for each partner
            $templates = $this->getProductTemplates($partner->name);
            foreach ($templates as $template) {
                ProductTemplate::create([
                    'partner_id' => $partner->id,
                    'name' => $template['name'],
                    'base_price' => $template['base_price'],
                    'default_markup_percent' => $template['markup'],
                    'is_active' => true,
                ]);
            }
        }

        // Create Box Templates
        $boxTemplates = [
            // Heavy Meal
            [
                'name' => 'Nasi Box A',
                'type' => 'heavy_meal',
                'price' => 25000,
                'items_json' => ['Nasi Putih', 'Ayam Goreng', 'Sambal', 'Lalapan', 'Kerupuk'],
            ],
            [
                'name' => 'Nasi Box B',
                'type' => 'heavy_meal',
                'price' => 30000,
                'items_json' => ['Nasi Putih', 'Rendang', 'Telur Dadar', 'Sambal', 'Lalapan'],
            ],
            [
                'name' => 'Nasi Box Premium',
                'type' => 'heavy_meal',
                'price' => 40000,
                'items_json' => ['Nasi Putih', 'Ayam Bakar', 'Ikan Goreng', 'Sayur Asem', 'Sambal', 'Kerupuk'],
            ],
            // Snack Box
            [
                'name' => 'Snack Box A',
                'type' => 'snack_box',
                'price' => 15000,
                'items_json' => ['Risoles', 'Pastel', 'Kue Sus', 'Air Mineral'],
            ],
            [
                'name' => 'Snack Box B',
                'type' => 'snack_box',
                'price' => 20000,
                'items_json' => ['Lemper', 'Dadar Gulung', 'Bolu Kukus', 'Onde-onde', 'Teh Kotak'],
            ],
        ];

        foreach ($boxTemplates as $template) {
            BoxTemplate::create([
                'name' => $template['name'],
                'type' => $template['type'],
                'price' => $template['price'],
                'items_json' => $template['items_json'],
                'is_active' => true,
            ]);
        }
    }

    private function getProductTemplates(string $partnerName): array
    {
        if (str_contains($partnerName, 'Gorengan')) {
            return [
                ['name' => 'Bakwan', 'base_price' => 1000, 'markup' => 10],
                ['name' => 'Tahu Isi', 'base_price' => 1500, 'markup' => 10],
                ['name' => 'Tempe Goreng', 'base_price' => 1000, 'markup' => 10],
                ['name' => 'Pisang Goreng', 'base_price' => 2000, 'markup' => 10],
            ];
        }

        if (str_contains($partnerName, 'Kue Basah')) {
            return [
                ['name' => 'Klepon', 'base_price' => 1500, 'markup' => 15],
                ['name' => 'Onde-onde', 'base_price' => 2000, 'markup' => 15],
                ['name' => 'Getuk', 'base_price' => 1500, 'markup' => 15],
            ];
        }

        if (str_contains($partnerName, 'Roti')) {
            return [
                ['name' => 'Roti Coklat', 'base_price' => 3000, 'markup' => 10],
                ['name' => 'Roti Keju', 'base_price' => 3500, 'markup' => 10],
                ['name' => 'Roti Srikaya', 'base_price' => 3000, 'markup' => 10],
            ];
        }

        return [];
    }
}
