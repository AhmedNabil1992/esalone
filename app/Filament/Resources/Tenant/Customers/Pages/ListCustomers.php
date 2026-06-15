<?php

namespace App\Filament\Resources\Tenant\Customers\Pages;

use App\Filament\Resources\Tenant\Customers\CustomerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
