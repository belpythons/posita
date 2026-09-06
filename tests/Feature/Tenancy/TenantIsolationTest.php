<?php

use App\Models\BoxOrder;
use App\Models\Outlet;
use App\Models\Partner;
use App\Models\ShopSession;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

beforeEach(function () {
    $this->tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
    $this->tenantB = Tenant::factory()->create(['name' => 'Tenant B']);

    Outlet::factory()->create(['tenant_id' => $this->tenantA->id, 'code' => 'A1']);
    Outlet::factory()->create(['tenant_id' => $this->tenantB->id, 'code' => 'B1']);
});

it('scopes queries to the current tenant', function () {
    $this->withTenant($this->tenantA, fn () => Partner::create(['name' => 'Partner A']));
    $this->withTenant($this->tenantB, fn () => Partner::create(['name' => 'Partner B']));

    $seenByA = $this->withTenant($this->tenantA, fn () => Partner::pluck('name')->all());
    $seenByB = $this->withTenant($this->tenantB, fn () => Partner::pluck('name')->all());

    expect($seenByA)->toBe(['Partner A']);
    expect($seenByB)->toBe(['Partner B']);
});

it('prevents route model binding across tenants', function () {
    $foreign = $this->withTenant($this->tenantB, fn () => Partner::create(['name' => 'Partner B']));

    $this->actingAsTenantUser($this->tenantA, ['role' => 'admin']);

    // 404, never 403: a 403 would confirm the record exists.
    $this->get("/admin/partners/{$foreign->id}/edit")->assertNotFound();
});

it('auto-fills tenant_id on create', function () {
    $partner = $this->withTenant($this->tenantA, fn () => Partner::create(['name' => 'Auto']));

    expect($partner->tenant_id)->toBe($this->tenantA->id);
});

it('prevents mass-assigning a foreign tenant_id', function () {
    $partner = $this->withTenant($this->tenantA, fn () => Partner::create([
        'name' => 'Smuggled',
        'tenant_id' => $this->tenantB->id,
    ]));

    expect($partner->tenant_id)->toBe($this->tenantA->id);
});

it('isolates aggregate queries and reports', function () {
    $this->withTenant($this->tenantA, fn () => BoxOrder::create([
        'customer_name' => 'A', 'quantity' => 1, 'total_price' => 100,
        'pickup_datetime' => now(), 'status' => 'completed',
    ]));
    $this->withTenant($this->tenantB, fn () => BoxOrder::create([
        'customer_name' => 'B', 'quantity' => 1, 'total_price' => 9999,
        'pickup_datetime' => now(), 'status' => 'completed',
    ]));

    $totalForA = $this->withTenant($this->tenantA, fn () => BoxOrder::sum('total_price'));

    expect((float) $totalForA)->toBe(100.0);
});

it('isolates data exports', function () {
    $session = $this->withTenant($this->tenantB, function () {
        $user = User::factory()->create(['tenant_id' => $this->tenantB->id]);

        return ShopSession::create([
            'user_id' => $user->id,
            'opened_at' => now()->subHour(),
            'closed_at' => now(),
            'opening_cash' => 0,
            'status' => 'closed',
        ]);
    });

    $this->actingAsTenantUser($this->tenantA, ['role' => 'admin']);

    $this->get("/admin/reports/session/{$session->id}")->assertNotFound();
});

it('rejects a user that belongs to no tenant and is not a super admin', function () {
    $this->actingAs(User::factory()->create(['tenant_id' => null, 'is_super_admin' => false]));

    $this->get('/profile')->assertForbidden();
});

it('lets a super admin through without a tenant', function () {
    $this->actingAs(User::factory()->superAdmin()->create());

    $this->get('/profile')->assertOk();
    expect(app(TenantContext::class)->id())->toBeNull();
});
