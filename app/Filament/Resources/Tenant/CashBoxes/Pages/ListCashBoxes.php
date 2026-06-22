<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Pages;

use App\Actions\Tenant\TransferCashBetweenCashBoxesAction;
use App\Filament\Resources\Tenant\CashBoxes\CashBoxResource;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCashBoxes extends ListRecords
{
    protected static string $resource = CashBoxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('transferBetweenCashBoxes')
                ->label('تحويل بين الخزن')
                ->icon('heroicon-o-arrows-right-left')
                ->color('warning')
                ->form([
                    Select::make('from_cash_box_id')
                        ->label('من خزنة')
                        ->options(fn (): array => CashBox::query()
                            ->where('is_active', true)
                            ->orderBy('name', 'asc')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->live(),
                    Select::make('to_cash_box_id')
                        ->label('إلى خزنة')
                        ->options(fn (callable $get): array => CashBox::query()
                            ->where('is_active', true)
                            ->when(
                                filled($get('from_cash_box_id')),
                                fn ($query) => $query->whereKeyNot((int) $get('from_cash_box_id')),
                            )
                            ->orderBy('name', 'asc')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required()
                        ->disabled(fn (callable $get): bool => blank($get('from_cash_box_id'))),
                    TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->minValue(0.01)
                        ->required(),
                    Textarea::make('description')
                        ->label('ملاحظات')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = Auth::user();

                    app(TransferCashBetweenCashBoxesAction::class)->execute($data, $actor);

                    Notification::make()
                        ->title('تم تحويل المبلغ بنجاح')
                        ->success()
                        ->send();
                }),
        ];
    }
}
