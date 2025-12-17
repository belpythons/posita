<?php

namespace App\Filament\Resources\DailyConsignmentResource\Pages;

use App\Filament\Resources\DailyConsignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyConsignment extends EditRecord
{
    protected static string $resource = DailyConsignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
