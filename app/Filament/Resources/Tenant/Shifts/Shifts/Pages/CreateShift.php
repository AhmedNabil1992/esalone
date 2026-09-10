<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Actions\Tenant\OpenShiftAction;
use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(OpenShiftAction::class)->execute(Auth::user(), $data);
    }
}
