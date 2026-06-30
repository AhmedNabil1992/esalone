<?php

namespace App\Filament\Resources\Tenant\Reservations\Pages;

use App\Filament\Resources\Tenant\Reservations\ReservationsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
