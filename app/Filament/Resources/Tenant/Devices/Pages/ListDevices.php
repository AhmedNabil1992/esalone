<?php

namespace App\Filament\Resources\Tenant\Devices\Pages;

use App\Filament\Resources\Tenant\Devices\DeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة الأجهزة'),
        ];
    }
}
