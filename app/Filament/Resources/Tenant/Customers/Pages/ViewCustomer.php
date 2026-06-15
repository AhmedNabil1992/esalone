<?php

namespace App\Filament\Resources\Tenant\Customers\Pages;

use App\Filament\Resources\Tenant\Customers\CustomerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
