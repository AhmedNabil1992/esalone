<?php

namespace App\Filament\Resources\Tenant\DeviceTypes\Pages;

use App\Filament\Resources\Tenant\DeviceTypes\DeviceTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDeviceType extends CreateRecord
{
    protected static string $resource = DeviceTypeResource::class;
}
