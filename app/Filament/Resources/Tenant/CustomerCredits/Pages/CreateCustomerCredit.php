<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Pages;

use App\Actions\Tenant\AddCustomerCreditAction;
use App\Filament\Resources\Tenant\CustomerCredits\CustomerCreditResource;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCustomerCredit extends CreateRecord
{
    protected static string $resource = CustomerCreditResource::class;

    protected function handleRecordCreation(array $data): CustomerCredit
    {
        /** @var User $user */
        $user = Auth::user();

        return app(AddCustomerCreditAction::class)->execute($data, $user);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
