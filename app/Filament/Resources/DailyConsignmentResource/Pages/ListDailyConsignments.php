<?php

namespace App\Filament\Resources\DailyConsignmentResource\Pages;

use App\Filament\Resources\DailyConsignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyConsignments extends ListRecords
{
    protected static string $resource = DailyConsignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Read-only
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DailyConsignmentResource\Widgets\DailyConsignmentStats::class,
        ];
    }
}
