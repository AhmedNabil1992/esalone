<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزنة')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->money()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->placeholder('-')
                    ->limit(40),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-'),
                TextColumn::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
