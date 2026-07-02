<?php

namespace App\Filament\Customer\Resources\CustomerPayments\Pages;

use App\Filament\Customer\Resources\CustomerPayments\CustomerPaymentsResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPayments extends ListRecords
{
    protected static string $resource = CustomerPaymentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
