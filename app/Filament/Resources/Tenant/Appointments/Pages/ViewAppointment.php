<?php

namespace App\Filament\Resources\Tenant\Appointments\Pages;

use App\Filament\Resources\Tenant\Appointments\AppointmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAppointment extends ViewRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
