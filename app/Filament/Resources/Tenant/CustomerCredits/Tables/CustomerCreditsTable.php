<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerCreditsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('العملية')
                    ->formatStateUsing(fn ($state) => $state === 'deposit' ? 'إيداع' : 'سحب')
                    ->badge()
                    ->color(fn ($state) => $state === 'deposit' ? 'success' : 'danger'),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->sortable(),

                TextColumn::make('balance_before')
                    ->label('الرصيد قبل')
                    ->money(),

                TextColumn::make('balance_after')
                    ->label('الرصيد بعد')
                    ->money(),

                TextColumn::make('user.name')
                    ->label('بواسطة')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('description')
                    ->label('ملاحظة')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
