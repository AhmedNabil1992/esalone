<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Pages;

use App\Actions\Tenant\AddCustomerCreditAction;
use App\Filament\Resources\Tenant\CustomerCredits\CustomerCreditResource;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateCustomerCredit extends CreateRecord
{
    protected static string $resource = CustomerCreditResource::class;

    protected function handleRecordCreation(array $data): CustomerCredit
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            return app(AddCustomerCreditAction::class)->execute($data, $user);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('خطأ في حركة الرصيد')
                ->body($e->getMessage())
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'amount' => $e->getMessage(),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
