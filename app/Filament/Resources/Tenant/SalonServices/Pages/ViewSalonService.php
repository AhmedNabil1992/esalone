<?php

namespace App\Filament\Resources\Tenant\SalonServices\Pages;

use App\Filament\Resources\Tenant\SalonServices\SalonServiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSalonService extends ViewRecord
{
    protected static string $resource = SalonServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
