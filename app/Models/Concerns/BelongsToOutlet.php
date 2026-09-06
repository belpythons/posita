<?php

namespace App\Models\Concerns;

use App\Models\Outlet;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fills outlet_id from the current context on create.
 *
 * Deliberately has no global scope: isolation is the tenant's job, an outlet is
 * only working context. A manager needs to read across the outlets of their own
 * tenant, so scoping reads by outlet here would break reports rather than
 * secure them.
 */
trait BelongsToOutlet
{
    public static function bootBelongsToOutlet(): void
    {
        static::creating(function (Model $model) {
            if (blank($model->outlet_id) && $outletId = app(TenantContext::class)->outletId()) {
                $model->outlet_id = $outletId;
            }
        });
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
