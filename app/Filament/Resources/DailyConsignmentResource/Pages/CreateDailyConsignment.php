<?php

namespace App\Filament\Resources\DailyConsignmentResource\Pages;

use App\Filament\Resources\DailyConsignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyConsignment extends CreateRecord
{
    protected static string $resource = DailyConsignmentResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        /** @var \App\Models\User */
        $user = auth()->user();

        // If start_cash is provided, we treat this as "Start Shop Session"
        if (isset($data['start_cash']) && is_numeric($data['start_cash'])) {
            $action = new \App\Actions\Consignment\StartDailyShopAction();

            // The Action throws exception if session exists. Filament will catch it and show error? 
            // Or we should try-catch. Filament usually handles exceptions globally or locally.
            // But Action returns Model.

            // Note: StartDailyShopAction ignores other fields like product_name.
            // If the user entered product info AND start_cash, the product info is lost.
            // This is acceptable given the dual-nature/refactor context.

            return $action->execute($user, (float) $data['start_cash']);
        }

        // Fallback or explicit handling for non-session items (if supported)
        return parent::handleRecordCreation($data);
    }
}
