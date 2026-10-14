<?php

namespace App\Filament\Resources\Tenant\SalonServices\Pages;

use App\Actions\Tenant\SaveSalonServiceAction;
use App\Filament\Resources\Tenant\SalonServices\SalonServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalonService extends CreateRecord
{
    protected static string $resource = SalonServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(SaveSalonServiceAction::class)->execute($data);
    }
}
