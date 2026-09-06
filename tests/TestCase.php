<?php

namespace Tests;

use App\Models\Outlet;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user belonging to $tenant, give them an outlet, and act as them.
     */
    protected function actingAsTenantUser(Tenant $tenant, array $attributes = []): User
    {
        $user = User::factory()->create(['tenant_id' => $tenant->getKey()] + $attributes);

        $outlet = $tenant->outlets()->first()
            ?? Outlet::factory()->create(['tenant_id' => $tenant->getKey()]);

        $user->outlets()->syncWithoutDetaching([$outlet->getKey() => ['is_primary' => true]]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Run a callback inside $tenant's context and restore the previous one.
     */
    protected function withTenant(Tenant $tenant, Closure $callback): mixed
    {
        return app(TenantContext::class)->runAs(
            $tenant->getKey(),
            $callback,
            $tenant->outlets()->first()?->getKey()
        );
    }

    protected function tearDown(): void
    {
        // The context is a singleton; leaking it across tests would make
        // isolation failures look like passes.
        app(TenantContext::class)->forget();

        parent::tearDown();
    }
}
