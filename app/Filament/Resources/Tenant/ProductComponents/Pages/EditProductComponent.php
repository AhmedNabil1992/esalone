<?php

namespace App\Filament\Resources\Tenant\ProductComponents\Pages;

use App\Filament\Resources\Tenant\ProductComponents\ProductComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProductComponent extends EditRecord
{
    protected static string $resource = ProductComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
