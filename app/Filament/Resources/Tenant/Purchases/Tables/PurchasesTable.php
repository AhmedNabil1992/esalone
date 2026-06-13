<?php

namespace App\Filament\Resources\Tenant\Purchases\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('المرجع')
                    ->searchable(),
                TextColumn::make('shift.shift_no')
                    ->label('رقم الشيفت')
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزنة')
                    ->searchable(),
                TextColumn::make('supplier.name')
                    ->label('المورد')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('purchased_at')
                    ->label('تاريخ الشراء')
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
