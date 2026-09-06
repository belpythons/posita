<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if ($tenantId = app(TenantContext::class)->id()) {
                // Qualifying the column is not cosmetic: DashboardService joins
                // daily_consignments to shop_sessions, and both carry tenant_id.
                // An unqualified predicate is an ambiguous column error there.
                $builder->where($builder->getModel()->getTable().'.tenant_id', $tenantId);
            }
        });

        static::creating(function (Model $model) {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId === null) {
                return;
            }

            // Assignment, not a blank() check: tenant_id is fillable, so a
            // request body could otherwise smuggle in a foreign tenant. Inside a
            // tenant context the context always wins. Outside one (migrations,
            // seeders, console) an explicitly set value stands.
            $model->tenant_id = $tenantId;
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Escape hatch — ONLY for system jobs and console commands. */
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }
}
