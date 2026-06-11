<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Pages;

use App\Filament\Resources\Tenant\PlaySessions\PlaySessionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlaySession extends ViewRecord
{
    protected static string $resource = PlaySessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
