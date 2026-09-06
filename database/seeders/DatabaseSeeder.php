<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed two tenants with completely separate data.
     *
     * Every block runs inside TenantContext::runAs, because the existing
     * seeders rely on the BelongsToTenant creating hook to stamp tenant_id.
     * Outside a context they would write rows with a null tenant and fail the
     * NOT NULL constraint.
     *
     * The second tenant deliberately gets a lighter dataset: BoxOrderSeeder
     * creates three months of orders and invokes stats:aggregate-daily once per
     * day, so duplicating it would roughly double the time of every
     * migrate:fresh --seed for no extra proof of isolation.
     */
    public function run(): void
    {
        $context = app(TenantContext::class);

        $main = $this->makeTenant('Toko Utama', 'toko-utama', [
            ['code' => 'PST', 'name' => 'Outlet Pusat'],
            ['code' => 'CBG', 'name' => 'Outlet Cabang'],
        ]);

        $context->runAs($main->getKey(), function () use ($main) {
            $this->call([
                UserSeeder::class,
                PartnerSeeder::class,
                BoxTemplateSeeder::class,
                ShopSessionSeeder::class,
                BoxOrderSeeder::class,
            ]);

            $this->attachUsersToOutlets($main);
        }, $main->outlets->first()->getKey());

        $second = $this->makeTenant('Kopi Senja', 'kopi-senja', [
            ['code' => 'SNJ', 'name' => 'Kopi Senja Utama'],
            ['code' => 'SNK', 'name' => 'Kopi Senja Kios'],
        ]);

        $context->runAs($second->getKey(), function () use ($second) {
            $this->call([SecondTenantSeeder::class]);

            $this->attachUsersToOutlets($second);
        }, $second->outlets->first()->getKey());
    }

    /**
     * @param  array<int, array{code: string, name: string}>  $outlets
     */
    private function makeTenant(string $name, string $slug, array $outlets): Tenant
    {
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'business_type' => 'coffee_shop',
            'plan' => 'free',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'locale' => 'id',
            'is_active' => true,
        ]);

        foreach ($outlets as $outlet) {
            Outlet::create([
                'tenant_id' => $tenant->getKey(),
                'name' => $outlet['name'],
                'code' => $outlet['code'],
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
            ]);
        }

        return $tenant->load('outlets');
    }

    private function attachUsersToOutlets(Tenant $tenant): void
    {
        $outlets = $tenant->outlets;
        $primaryId = $outlets->first()->getKey();

        User::where('tenant_id', $tenant->getKey())->each(
            fn (User $user) => $user->outlets()->sync(
                $outlets->mapWithKeys(fn (Outlet $outlet) => [
                    $outlet->getKey() => ['is_primary' => $outlet->getKey() === $primaryId],
                ])->all()
            )
        );
    }
}
