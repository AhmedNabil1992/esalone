<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Tables;

use App\Actions\Tenant\AdjustCashBoxBalanceAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CashBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')->label('الفرع')->badge()->sortable()->toggleable(),
                TextColumn::make('name')->label('الإسم')->searchable()->sortable(),
                TextColumn::make('code')->label('الرمز')->searchable()->sortable(),
                TextColumn::make('type')->label('النوع')->searchable()->sortable()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'cash' => 'كاش',
                        'bank' => 'بنك',
                        'wallet' => 'محفظة إلكترونية',
                        default => '-',
                    }),
                TextColumn::make('opening_balance')->label('الرصيد الافتتاحي')->money()->sortable(),
                TextColumn::make('current_balance')->label('الرصيد الحالي')->money()->sortable(),
                ToggleColumn::make('is_active')->label('نشط')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('adjust_balance')
                    ->label('تعديل الرصيد / تسوية')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->modalHeading(fn ($record) => "تعديل رصيد الخزينة ({$record->name})")
                    ->modalDescription('يمكنك تعديل رصيد الخزينة بسبب مسحوبات مالك أو تسوية عجز/زيادة في الإيراد.')
                    ->schema([
                        Select::make('reason_type')
                            ->label('سبب التعديل / العملية')
                            ->options([
                                'owner_withdrawal' => 'مسحوبات مالك (سحب صاحب المحل)',
                                'shift_deficit' => 'تسوية عجز إيراد / وردية (خصم)',
                                'shift_surplus' => 'تسوية زيادة إيراد / وردية (إضافة)',
                                'cashbox_adjustment' => 'تسوية رصيد خزانة عامة',
                            ])
                            ->default('owner_withdrawal')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (in_array($state, ['owner_withdrawal', 'shift_deficit'], true)) {
                                    $set('direction', 'out');
                                } elseif ($state === 'shift_surplus') {
                                    $set('direction', 'in');
                                }
                            }),
                        Select::make('direction')
                            ->label('اتجاه الحركة')
                            ->options([
                                'out' => 'خصم / سحب من الخزنة',
                                'in' => 'إضافة / توريد للخزنة',
                            ])
                            ->default('out')
                            ->required(),
                        TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->prefix('ج.م')
                            ->minValue(0.01)
                            ->required(),
                        Textarea::make('description')
                            ->label('البيان / ملاحظات')
                            ->placeholder('أدخل سبب تعديل الرصيد...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data): void {
                        $payload = [
                            'cash_box_id' => $record->id,
                            'direction' => $data['direction'],
                            'reason_type' => $data['reason_type'],
                            'amount' => $data['amount'],
                            'description' => $data['description'] ?? null,
                        ];

                        (new AdjustCashBoxBalanceAction)->execute($payload, auth()->user());

                        Notification::make()
                            ->title('تم تعديل رصيد الخزينة وحفظ القيد بنجاح')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
