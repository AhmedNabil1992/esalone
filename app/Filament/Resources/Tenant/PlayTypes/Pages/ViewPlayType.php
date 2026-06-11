<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Pages;

use App\Filament\Resources\Tenant\PlayTypes\PlayTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlayType extends ViewRecord
{
    protected static string $resource = PlayTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
