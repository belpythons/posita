<?php

namespace App\Support\Tenancy;

use Closure;

/**
 * The current tenant and outlet for this request, command or queued job.
 *
 * Registered as a singleton. Everything that scopes data reads from here, and
 * nothing ever reads a tenant id from a request body — that is a security rule,
 * not a convention.
 */
final class TenantContext
{
    private ?int $tenantId = null;

    private ?int $outletId = null;

    public function set(?int $tenantId, ?int $outletId = null): void
    {
        $this->tenantId = $tenantId;
        $this->outletId = $outletId;
    }

    public function setOutlet(?int $outletId): void
    {
        $this->outletId = $outletId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function outletId(): ?int
    {
        return $this->outletId;
    }

    public function forget(): void
    {
        $this->tenantId = null;
        $this->outletId = null;
    }

    /**
     * Run a callback inside another tenant's context, then restore the previous
     * one — including when the callback throws.
     */
    public function runAs(int $tenantId, Closure $callback, ?int $outletId = null): mixed
    {
        $previousTenant = $this->tenantId;
        $previousOutlet = $this->outletId;

        $this->set($tenantId, $outletId);

        try {
            return $callback();
        } finally {
            $this->set($previousTenant, $previousOutlet);
        }
    }
}
