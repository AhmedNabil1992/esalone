<?php

namespace App\Filament\Resources\Tenant\DeviceTypes\Pages;

use App\Filament\Resources\Tenant\DeviceTypes\DeviceTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDeviceType extends ViewRecord
{
    protected static string $resource = DeviceTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
