<?php

namespace App\Filament\Resources\Tenant\Reservations\Pages;

use App\Filament\Resources\Tenant\Reservations\ReservationsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReservations extends EditRecord
{
    protected static string $resource = ReservationsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
