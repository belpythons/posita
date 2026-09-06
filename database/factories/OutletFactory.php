<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outlet>
 */
class OutletFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Outlet '.fake()->city(),
            'code' => Str::upper(Str::random(6)),
            'geofence_radius_m' => 100,
            'timezone' => 'Asia/Jakarta',
            'is_active' => true,
        ];
    }
}
