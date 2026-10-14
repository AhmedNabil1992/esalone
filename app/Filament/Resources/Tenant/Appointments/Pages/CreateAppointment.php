<?php

namespace App\Filament\Resources\Tenant\Appointments\Pages;

use App\Actions\Tenant\SaveAppointmentAction;
use App\Filament\Resources\Tenant\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveAppointmentAction::class)->execute($data);
    }
}
