<?php

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Queue;

/**
 * Records the tenant id visible inside handle(), so a test can assert what the
 * worker actually saw rather than what it was told to set.
 */
class RecordTenantJob implements ShouldQueue
{
    use App\Jobs\Concerns\TenantAware;
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var array<int, int|null> */
    public static array $seen = [];

    public function __construct()
    {
        $this->captureTenantContext();
    }

    public function handle(TenantContext $context): void
    {
        static::$seen[] = $context->id();
    }
}

beforeEach(function () {
    RecordTenantJob::$seen = [];

    $this->tenantA = Tenant::factory()->create();
    $this->tenantB = Tenant::factory()->create();
});

it('restores tenant context inside a queued job', function () {
    $this->withTenant($this->tenantA, fn () => Queue::push(new RecordTenantJob));

    expect(RecordTenantJob::$seen)->toBe([$this->tenantA->id]);
});

it('clears tenant context after the job finishes', function () {
    $this->withTenant($this->tenantA, fn () => Queue::push(new RecordTenantJob));

    expect(app(TenantContext::class)->id())->toBeNull();
});

it('does not leak context between jobs of different tenants', function () {
    $jobA = $this->withTenant($this->tenantA, fn () => new RecordTenantJob);
    $jobB = $this->withTenant($this->tenantB, fn () => new RecordTenantJob);

    // Same worker, back to back, with no context bound between them.
    Queue::push($jobA);
    Queue::push($jobB);

    expect(RecordTenantJob::$seen)->toBe([$this->tenantA->id, $this->tenantB->id]);
    expect(app(TenantContext::class)->id())->toBeNull();
});
