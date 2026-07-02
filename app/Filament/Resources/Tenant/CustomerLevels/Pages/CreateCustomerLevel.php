<?php

namespace App\Filament\Resources\Tenant\CustomerLevels\Pages;

use App\Filament\Resources\Tenant\CustomerLevels\CustomerLevelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerLevel extends CreateRecord
{
    protected static string $resource = CustomerLevelResource::class;
}
