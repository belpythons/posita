<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            // Tenant first, then outlet: outlet resolution reads the tenant
            // context to scope the user's accessible outlets.
            \App\Http\Middleware\EnsureTenantContext::class,
            \App\Http\Middleware\ResolveOutletContext::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Appending alone is not enough: SubstituteBindings would still run
        // first, and route model binding would resolve another tenant's record
        // before any scope exists. Forcing both middleware ahead of it in the
        // priority list is what turns a cross-tenant URL into a 404.
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\EnsureTenantContext::class,
        );
        $middleware->prependToPriorityList(
            before: \Illuminate\Routing\Middleware\SubstituteBindings::class,
            prepend: \App\Http\Middleware\ResolveOutletContext::class,
        );

        // Register role middleware alias
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Redirect authenticated users based on role
        $middleware->redirectUsersTo(function () {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user?->role === 'admin') {
                return '/admin';
            }

            return '/pos/open';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
