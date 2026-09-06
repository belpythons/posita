<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'business_type' => 'coffee_shop',
            'plan' => 'free',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
            'is_active' => true,
        ];
    }
}
