<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Tenant\SystemConfig as Setting;
class ExpenseHistoriesTable
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
                TextColumn::make('expenseType.name')
                    ->label('نوع المصروف')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->placeholder('-')
                    ->limit(40),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->money()
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-'),
                TextColumn::make('shift_id')
                    ->label('رقم الشفت')
                    ->sortable(),
                TextColumn::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime()
                    ->sortable()
                    ,
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
