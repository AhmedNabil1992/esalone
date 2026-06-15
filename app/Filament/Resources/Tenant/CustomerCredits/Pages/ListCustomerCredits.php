<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Pages;

use App\Filament\Resources\Tenant\CustomerCredits\CustomerCreditResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerCredits extends ListRecords
{
    protected static string $resource = CustomerCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
