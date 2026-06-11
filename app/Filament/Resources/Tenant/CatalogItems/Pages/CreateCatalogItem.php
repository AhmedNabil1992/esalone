<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Pages;

use App\Filament\Resources\Tenant\CatalogItems\CatalogItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCatalogItem extends CreateRecord
{
    protected static string $resource = CatalogItemResource::class;
}
