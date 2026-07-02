<?php

namespace App\Filament\Resources\Tenant\CustomerLevels\Pages;

use App\Filament\Resources\Tenant\CustomerLevels\CustomerLevelResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerLevel extends EditRecord
{
    protected static string $resource = CustomerLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
