<?php

namespace App\Filament\Customer\Resources\CustomerPayments\Pages;

use App\Filament\Customer\Resources\CustomerPayments\CustomerPaymentsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPayments extends EditRecord
{
    protected static string $resource = CustomerPaymentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // DeleteAction::make(),
        ];
    }
}
