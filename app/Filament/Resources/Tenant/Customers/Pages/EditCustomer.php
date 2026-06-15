<?php

namespace App\Filament\Resources\Tenant\Customers\Pages;

use App\Filament\Resources\Tenant\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
