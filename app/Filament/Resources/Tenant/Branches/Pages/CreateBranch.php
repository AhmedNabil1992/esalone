<?php

namespace App\Filament\Resources\Tenant\Branches\Pages;

use App\Filament\Resources\Tenant\Branches\BranchResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBranch extends CreateRecord
{
    protected static string $resource = BranchResource::class;

    public function mount(): void
    {
        $tenant = tenancy()->tenant ?? (function_exists('tenant') ? tenant() : null);

        if ($tenant && ! $tenant->canCreateBranch()) {
            Notification::make()
                ->title('عفواً، لقد وصلت للحد الأقصى المسموح به من الفروع')
                ->body("الحد الأقصى المسموح به لحسابك هو {$tenant->getBranchesLimit()} فرع. يرجى ترقية الباقة لإضافة فروع جديدة.")
                ->danger()
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));

            return;
        }

        parent::mount();
    }

    protected function beforeCreate(): void
    {
        $tenant = tenancy()->tenant ?? (function_exists('tenant') ? tenant() : null);

        if ($tenant && ! $tenant->canCreateBranch()) {
            Notification::make()
                ->title('عفواً، لقد وصلت للحد الأقصى المسموح به من الفروع')
                ->body("الحد الأقصى المسموح به لحسابك هو {$tenant->getBranchesLimit()} فرع. يرجى ترقية الباقة لإضافة فروع جديدة.")
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }
    }
}
