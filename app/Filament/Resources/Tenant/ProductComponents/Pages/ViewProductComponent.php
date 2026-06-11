<?php

namespace App\Filament\Resources\Tenant\ProductComponents\Pages;

use App\Filament\Resources\Tenant\ProductComponents\ProductComponentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProductComponent extends ViewRecord
{
    protected static string $resource = ProductComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
