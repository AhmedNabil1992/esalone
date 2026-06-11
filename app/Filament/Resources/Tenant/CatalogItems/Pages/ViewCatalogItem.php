<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Pages;

use App\Filament\Resources\Tenant\CatalogItems\CatalogItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCatalogItem extends ViewRecord
{
    protected static string $resource = CatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
