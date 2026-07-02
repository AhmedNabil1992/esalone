<?php

namespace App\Filament\Customer\Resources\CustomerPayments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->searchable()
                    ->label('الوصف'),
                TextColumn::make('type')
                    ->badge()
                    ->label('النوع')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'withdraw' => 'سحب',
                        'deposit' => 'إيداع',
                        'redeem' => 'تبديل نقاط',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'withdraw' => 'danger',
                        'deposit' => 'success',
                        'redeem' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('balance_before')
                    ->money()
                    ->label('الرصيد قبل')
                    ->color('gray'),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money(),
                TextColumn::make('balance_after')
                    ->money()
                    ->label('الرصيد بعد')
                    ->color('gray'),
                TextColumn::make('user.name')
                    ->label('الكاشير'),
                TextColumn::make('created_at')
                    ->label('تاريخ الاضافة')
                    ->dateTime('d/m/Y H:i A'),
                TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
                    ->dateTime('d/m/Y H:i A')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
