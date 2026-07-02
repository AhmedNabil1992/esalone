<?php

namespace App\Filament\Resources\Tenant\CustomerLevels\Pages;

use App\Filament\Resources\Tenant\CustomerLevels\CustomerLevelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLevels extends ListRecords
{
    protected static string $resource = CustomerLevelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
