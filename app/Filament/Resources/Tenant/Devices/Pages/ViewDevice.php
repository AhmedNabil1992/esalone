<?php

namespace App\Filament\Resources\Tenant\Devices\Pages;

use App\Filament\Resources\Tenant\Devices\DeviceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
