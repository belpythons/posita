<?php

namespace App\Console\Commands;

use App\Models\BoxOrder;
use App\Models\Outlet;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDailyStats extends Command
{
    protected $signature = 'stats:aggregate-daily {date? : The date to aggregate (YYYY-MM-DD)}';

    protected $description = 'Aggregate daily transactions into daily_stats table';

    public function handle(TenantContext $context): int
    {
        $date = $this->argument('date')
            ? Carbon::parse($this->argument('date'))->toDateString()
            : Carbon::yesterday()->toDateString();

        $this->info("Aggregating stats for: {$date}");

        // A console command has no authenticated user, so there is no tenant
        // context to inherit. Without this loop the Eloquent queries below would
        // still be scoped -- to a null tenant -- and quietly write zeros every
        // night. Tenants and outlets are read outside any tenant scope, which is
        // why this command is the one place withoutTenantScope() is allowed.
        $tenants = Tenant::where('is_active', true)->get();

        foreach ($tenants as $tenant) {
            $outlets = Outlet::withoutTenantScope()
                ->where('tenant_id', $tenant->getKey())
                ->get();

            foreach ($outlets as $outlet) {
                $context->runAs(
                    $tenant->getKey(),
                    fn () => $this->aggregate($tenant->getKey(), $outlet->getKey(), $date),
                    $outlet->getKey()
                );
            }
        }

        $this->info('Daily stats aggregated successfully.');

        return self::SUCCESS;
    }

    private function aggregate(int $tenantId, int $outletId, string $date): void
    {
        $ordersQuery = BoxOrder::whereDate('created_at', $date)
            ->where('outlet_id', $outletId)
            ->whereIn('status', ['paid', 'completed']);

        $totalRevenue = (clone $ordersQuery)->sum('total_price');
        $totalTransactions = (clone $ordersQuery)->count();

        // Raw builder: no global scope applies to either side of this join, so
        // both tenant and outlet are filtered explicitly.
        $totalItemsSold = DB::table('box_order_items')
            ->join('box_orders', 'box_order_items.box_order_id', '=', 'box_orders.id')
            ->whereDate('box_orders.created_at', $date)
            ->where('box_orders.tenant_id', $tenantId)
            ->where('box_orders.outlet_id', $outletId)
            ->whereIn('box_orders.status', ['paid', 'completed'])
            ->sum('box_order_items.quantity');

        $jsonData = [
            'summary' => [
                'revenue' => $totalRevenue,
                'transactions' => $totalTransactions,
            ],
            'generated_at' => now()->toDateTimeString(),
        ];

        // The match array must carry tenant and outlet: `date` alone used to be
        // globally unique, and matching on it again would let one tenant's run
        // overwrite another's row.
        DB::table('daily_stats')->updateOrInsert(
            ['tenant_id' => $tenantId, 'outlet_id' => $outletId, 'date' => $date],
            [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'total_items_sold' => $totalItemsSold,
                'json_data' => json_encode($jsonData),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
