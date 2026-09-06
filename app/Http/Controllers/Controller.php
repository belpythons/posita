<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

abstract class Controller
{
    /**
     * An `exists` rule constrained to the current tenant.
     *
     * Laravel's presence verifier builds a raw query-builder query against the
     * table, so it never touches the model and never applies the tenant global
     * scope. A plain `exists:partners,id` therefore accepts another tenant's id
     * as valid. Every exists rule on a tenant-owned table must go through here.
     */
    protected function existsInTenant(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)
            ->where('tenant_id', app(TenantContext::class)->id());
    }
}
