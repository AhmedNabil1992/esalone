<?php

namespace App\Filament\Resources\Tenant\Customers\Pages;

use App\Filament\Resources\Tenant\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
