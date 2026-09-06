<?php

namespace App\Jobs\Concerns;

use App\Support\Tenancy\TenantContext;

/**
 * Carries the dispatching tenant across the queue boundary.
 *
 * Capture happens here; restoring and clearing is done by the global
 * Queue::before / Queue::after / Queue::failing listeners in AppServiceProvider
 * rather than by handle(), because a listener cannot be forgotten. See the
 * comment there for why that matters.
 */
trait TenantAware
{
    public ?int $tenantId = null;

    public ?int $outletId = null;

    public function captureTenantContext(): void
    {
        $context = app(TenantContext::class);

        $this->tenantId = $context->id();
        $this->outletId = $context->outletId();
    }
}
