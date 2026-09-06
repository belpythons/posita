<?php

namespace App\Providers;

use App\Jobs\Concerns\TenantAware;
use App\Support\Tenancy\TenantContext;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        \App\Models\ShopSession::observe(\App\Observers\ShopSessionObserver::class);

        $this->registerTenantAwareQueue();
    }

    /**
     * Restore and clear tenant context around every queued job.
     *
     * P01 offers two options: have the job restore its own context in handle(),
     * or hook the queue globally. The global listener wins because a trait
     * cannot force handle() to call it — one forgetful developer and a job runs
     * under the previous job's tenant, which is a silent security bug.
     *
     * Clearing on both `processed` and `failed` matters: a worker is a
     * long-lived process, and a job that throws must not bequeath its tenant to
     * whatever runs next.
     */
    private function registerTenantAwareQueue(): void
    {
        $context = $this->app->make(TenantContext::class);

        Queue::before(function (JobProcessing $event) use ($context) {
            $command = $this->resolveCommand($event);

            if ($command !== null && in_array(TenantAware::class, class_uses_recursive($command), true)) {
                $context->set($command->tenantId, $command->outletId);
            }
        });

        Queue::after(fn (JobProcessed $event) => $context->forget());
        Queue::failing(fn (JobFailed $event) => $context->forget());
    }

    private function resolveCommand(JobProcessing $event): ?object
    {
        $payload = $event->job->payload();

        if (! isset($payload['data']['command'])) {
            return null;
        }

        $command = $payload['data']['command'];

        return is_string($command) ? @unserialize($command) ?: null : $command;
    }
}
