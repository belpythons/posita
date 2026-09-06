<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the tenant of the authenticated user for the rest of the request.
 *
 * The tenant is always taken from the authenticated user, never from the
 * request — a client-supplied tenant id is a cross-tenant read waiting to
 * happen.
 */
class EnsureTenantContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->tenant_id === null) {
            // A platform super admin legitimately belongs to no tenant. Anyone
            // else without one has no data to see.
            abort_unless($user->is_super_admin, 403, __('tenancy.no_tenant'));

            return $next($request);
        }

        $this->context->set($user->tenant_id);

        return $next($request);
    }
}
