<?php

use App\Models\BoxOrder;
use App\Models\DailyConsignment;
use App\Models\Outlet;
use App\Models\Partner;
use App\Models\ShopSession;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AdminDataService;
use Illuminate\Support\Facades\DB;

/**
 * The tenant global scope is not a complete defence. These cover the four
 * places it demonstrably does not reach: the cache, the console, validation
 * rules, and request-supplied ids inside a tenant.
 */
beforeEach(function () {
    $this->tenantA = Tenant::factory()->create();
    $this->tenantB = Tenant::factory()->create();

    $this->outletA = Outlet::factory()->create(['tenant_id' => $this->tenantA->id, 'code' => 'A1']);
    $this->outletB = Outlet::factory()->create(['tenant_id' => $this->tenantB->id, 'code' => 'B1']);
});

it('does not serve one tenant cached partners to another', function () {
    $this->withTenant($this->tenantA, fn () => Partner::create(['name' => 'Only A']));
    $this->withTenant($this->tenantB, fn () => Partner::create(['name' => 'Only B']));

    $service = app(AdminDataService::class);

    // Tenant A warms the cache first; tenant B must not read A's entry.
    $forA = $this->withTenant($this->tenantA, fn () => $service->getPartners(true)->pluck('name')->all());
    $forB = $this->withTenant($this->tenantB, fn () => $service->getPartners(true)->pluck('name')->all());

    expect($forA)->toBe(['Only A']);
    expect($forB)->toBe(['Only B']);
});

it('aggregates daily stats per tenant instead of writing zeros', function () {
    $date = now()->subDay();

    foreach ([[$this->tenantA, $this->outletA, 100], [$this->tenantB, $this->outletB, 250]] as [$tenant, $outlet, $price]) {
        app(App\Support\Tenancy\TenantContext::class)->runAs($tenant->id, function () use ($price, $date) {
            $order = BoxOrder::create([
                'customer_name' => 'X',
                'quantity' => 1,
                'total_price' => $price,
                'pickup_datetime' => $date,
                'status' => 'completed',
            ]);
            $order->items()->create([
                'product_name' => 'X', 'quantity' => 2, 'unit_price' => $price, 'subtotal' => $price,
            ]);

            // created_at is not fillable, so it has to be backdated after the
            // insert for the aggregation to see the order on that day.
            $order->forceFill(['created_at' => $date])->save();
        }, $outlet->id);
    }

    $this->artisan('stats:aggregate-daily', ['date' => $date->toDateString()])->assertSuccessful();

    $rowA = DB::table('daily_stats')
        ->where(['tenant_id' => $this->tenantA->id, 'outlet_id' => $this->outletA->id])->first();
    $rowB = DB::table('daily_stats')
        ->where(['tenant_id' => $this->tenantB->id, 'outlet_id' => $this->outletB->id])->first();

    expect((float) $rowA->total_revenue)->toBe(100.0);
    expect((float) $rowB->total_revenue)->toBe(250.0);
    expect((int) $rowA->total_items_sold)->toBe(2);
});

it('rejects a foreign tenant id in an exists validation rule', function () {
    $foreignPartner = $this->withTenant($this->tenantB, fn () => Partner::create(['name' => 'B']));

    $user = $this->actingAsTenantUser($this->tenantA);

    ShopSession::create([
        'user_id' => $user->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => 'open',
    ]);

    $this->post('/pos/consignment', [
        'partner_id' => $foreignPartner->id,
        'product_name' => 'Smuggled',
        'qty_initial' => 1,
        'base_price' => 1000,
        'selling_price' => 2000,
    ])->assertSessionHasErrors('partner_id');
});

it('refuses to bulk update a consignment outside the caller session', function () {
    $owner = $this->actingAsTenantUser($this->tenantA);

    $othersSession = ShopSession::create([
        'user_id' => User::factory()->create(['tenant_id' => $this->tenantA->id])->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => 'open',
    ]);

    $partner = Partner::create(['name' => 'P']);

    $victim = DailyConsignment::create([
        'shop_session_id' => $othersSession->id,
        'partner_id' => $partner->id,
        'product_name' => 'Kue',
        'qty_initial' => 10,
        'qty_sold' => 0,
        'qty_remaining' => 10,
        'base_price' => 1000,
        'selling_price' => 2000,
        'markup_percent' => 0,
        'subtotal_income' => 0,
    ]);

    // The caller has their own open session, so the request is authorised —
    // but the id belongs to a colleague's session.
    ShopSession::create([
        'user_id' => $owner->id,
        'opened_at' => now(),
        'opening_cash' => 0,
        'status' => 'open',
    ]);

    $this->post('/pos/consignment/bulk-update', [
        'items' => [['id' => $victim->id, 'qty_sold' => 10]],
    ]);

    expect($victim->fresh()->qty_sold)->toBe(0);
});
