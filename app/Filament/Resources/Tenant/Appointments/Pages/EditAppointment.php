<?php

namespace App\Filament\Resources\Tenant\Appointments\Pages;

use App\Actions\Tenant\SaveAppointmentAction;
use App\Filament\Resources\Tenant\Appointments\AppointmentResource;
use App\Models\Tenant\Appointment;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Appointment) {
            throw new \LogicException('Unexpected record type for appointment resource.');
        }

        return app(SaveAppointmentAction::class)->execute($data, $record);
    }
}
