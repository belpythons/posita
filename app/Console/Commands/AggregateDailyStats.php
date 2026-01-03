<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AggregateDailyStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stats:aggregate-daily {date? : The date to aggregate (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate daily transactions into daily_stats table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date')
            ? \Carbon\Carbon::parse($this->argument('date'))->toDateString()
            : \Carbon\Carbon::yesterday()->toDateString();

        $this->info("Aggregating stats for: {$date}");

        // 1. Get Base Orders Query
        $ordersQuery = \App\Models\BoxOrder::whereDate('created_at', $date)
            ->whereIn('status', ['paid', 'completed']);

        // 2. Calculate Main Metrics
        $totalRevenue = $ordersQuery->sum('total_price');
        $totalTransactions = $ordersQuery->count();
        $totalItemsSold = \Illuminate\Support\Facades\DB::table('box_order_items')
            ->join('box_orders', 'box_order_items.box_order_id', '=', 'box_orders.id')
            ->whereDate('box_orders.created_at', $date)
            ->whereIn('box_orders.status', ['paid', 'completed'])
            ->sum('box_order_items.quantity');

        // 3. Breakdown - Simply Total for now as payment_method column is missing
        $jsonData = [
            'summary' => [
                'revenue' => $totalRevenue,
                'transactions' => $totalTransactions,
            ],
            'generated_at' => now()->toDateTimeString(),
        ];

        // 4. Update or Insert
        \Illuminate\Support\Facades\DB::table('daily_stats')->updateOrInsert(
            ['date' => $date],
            [
                'total_revenue' => $totalRevenue,
                'total_transactions' => $totalTransactions,
                'total_items_sold' => $totalItemsSold,
                'json_data' => json_encode($jsonData),
                'updated_at' => now(),
            ]
        );

        $this->info("Daily stats aggregated successfully.");
    }
}
