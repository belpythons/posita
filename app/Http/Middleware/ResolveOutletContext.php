<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which outlet the request is working in.
 *
 * Order: X-Outlet-Id header (for the API of P10) -> session (web) -> the user's
 * primary outlet. An outlet the user has no access to is rejected outright
 * rather than silently falling back, so a wrong header never reads as success.
 */
class ResolveOutletContext
{
    public function __construct(private readonly TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $this->context->id() === null) {
            return $next($request);
        }

        $requested = $request->header('X-Outlet-Id') ?? $request->session()->get('outlet_id');

        if ($requested !== null) {
            abort_unless(
                $this->userHasOutlet($user, (int) $requested),
                403,
                __('tenancy.outlet_forbidden')
            );

            $this->context->setOutlet((int) $requested);

            return $next($request);
        }

        $this->context->setOutlet($this->primaryOutletId($user));

        return $next($request);
    }

    private function userHasOutlet($user, int $outletId): bool
    {
        return $user->outlets()->whereKey($outletId)->exists();
    }

    private function primaryOutletId($user): ?int
    {
        $outlets = $user->outlets()->orderByDesc('outlet_user.is_primary')->first();

        return $outlets?->getKey();
    }
}
