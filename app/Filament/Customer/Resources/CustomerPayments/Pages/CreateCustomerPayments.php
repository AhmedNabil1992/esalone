<?php

namespace App\Filament\Customer\Resources\CustomerPayments\Pages;

use App\Filament\Customer\Resources\CustomerPayments\CustomerPaymentsResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPayments extends CreateRecord
{
    protected static string $resource = CustomerPaymentsResource::class;
}
