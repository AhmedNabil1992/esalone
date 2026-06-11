<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Pages;

use App\Filament\Resources\Tenant\CatalogItems\CatalogItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogItems extends ListRecords
{
    protected static string $resource = CatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
