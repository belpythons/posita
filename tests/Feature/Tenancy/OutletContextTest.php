<?php

use App\Models\Outlet;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();

    $this->primary = Outlet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'code' => 'PRI',
    ]);
    $this->secondary = Outlet::factory()->create([
        'tenant_id' => $this->tenant->id,
        'code' => 'SEC',
    ]);
});

it('resolves outlet from X-Outlet-Id header', function () {
    $user = $this->actingAsTenantUser($this->tenant);
    $user->outlets()->syncWithoutDetaching([$this->secondary->id => ['is_primary' => false]]);

    $this->withHeader('X-Outlet-Id', (string) $this->secondary->id)
        ->get('/profile')
        ->assertOk();

    expect(app(TenantContext::class)->outletId())->toBe($this->secondary->id);
});

it('rejects an outlet the user has no access to', function () {
    $foreign = Outlet::factory()->create(['code' => 'OTH']);

    $this->actingAsTenantUser($this->tenant);

    $this->withHeader('X-Outlet-Id', (string) $foreign->id)
        ->get('/profile')
        ->assertForbidden();
});

it('falls back to the user primary outlet when header is absent', function () {
    $user = $this->actingAsTenantUser($this->tenant);
    $user->outlets()->syncWithoutDetaching([$this->secondary->id => ['is_primary' => false]]);

    $this->get('/profile')->assertOk();

    expect(app(TenantContext::class)->outletId())->toBe($this->primary->id);
});

it('remembers the outlet chosen through the switch route', function () {
    $user = $this->actingAsTenantUser($this->tenant);
    $user->outlets()->syncWithoutDetaching([$this->secondary->id => ['is_primary' => false]]);

    $this->from('/profile')
        ->post('/outlet/switch', ['outlet_id' => $this->secondary->id])
        ->assertRedirect('/profile');

    $this->get('/profile')->assertOk();

    expect(app(TenantContext::class)->outletId())->toBe($this->secondary->id);
});

it('refuses to switch to an outlet of another tenant', function () {
    $foreign = Outlet::factory()->create(['code' => 'OTH']);

    $this->actingAsTenantUser($this->tenant);

    $this->post('/outlet/switch', ['outlet_id' => $foreign->id])->assertForbidden();
});

it('stamps the current outlet on records it creates', function () {
    $user = $this->actingAsTenantUser($this->tenant);
    $user->outlets()->syncWithoutDetaching([$this->secondary->id => ['is_primary' => false]]);

    $this->withHeader('X-Outlet-Id', (string) $this->secondary->id)->get('/profile');

    $session = App\Models\ShopSession::create([
        'user_id' => $user->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => 'open',
    ]);

    expect($session->outlet_id)->toBe($this->secondary->id);
});
