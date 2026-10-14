<?php

namespace App\Filament\Resources\Tenant\SalonServices\Pages;

use App\Filament\Resources\Tenant\SalonServices\SalonServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSalonServices extends ListRecords
{
    protected static string $resource = SalonServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
