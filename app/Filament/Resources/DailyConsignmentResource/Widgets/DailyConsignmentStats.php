<?php

namespace App\Filament\Resources\DailyConsignmentResource\Widgets;

use App\Models\DailyConsignment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class DailyConsignmentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $todayProfit = DailyConsignment::whereDate('date', Carbon::today())
            ->sum('total_profit');

        return [
            Stat::make('Total Profit Today', '$' . number_format($todayProfit, 2))
                ->description('Profit from consignments for ' . Carbon::today()->toFormattedDateString())
                ->color('success'),
        ];
    }
}
