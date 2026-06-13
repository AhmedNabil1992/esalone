<?php

namespace App\Filament\Resources\Tenant\Purchases\Pages;

use App\Actions\Tenant\ReversePurchaseAction;
use App\Filament\Resources\Tenant\Purchases\PurchaseResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchase extends ViewRecord
{
    protected static string $resource = PurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reversePurchase')
                ->label('عكس الفاتورة')
                ->color('danger')
                ->icon('heroicon-o-arrow-uturn-left')
                ->requiresConfirmation()
                ->visible(fn (): bool => ReversePurchaseAction::canReverse((string) $this->getRecord()->status))
                ->action(function (): void {
                    app(ReversePurchaseAction::class)->execute($this->getRecord());

                    Notification::make()
                        ->title('تم عكس الفاتورة بنجاح')
                        ->success()
                        ->send();

                    $this->refreshFormData([
                        'status',
                    ]);
                }),
        ];
    }
}
