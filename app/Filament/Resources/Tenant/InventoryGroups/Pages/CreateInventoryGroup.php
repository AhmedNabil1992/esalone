<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Pages;

use App\Filament\Resources\Tenant\InventoryGroups\InventoryGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryGroup extends CreateRecord
{
    protected static string $resource = InventoryGroupResource::class;
}
