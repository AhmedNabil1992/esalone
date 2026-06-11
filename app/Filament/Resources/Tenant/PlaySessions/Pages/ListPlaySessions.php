<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Pages;

use App\Filament\Resources\Tenant\PlaySessions\PlaySessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPlaySessions extends ListRecords
{
    protected static string $resource = PlaySessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
