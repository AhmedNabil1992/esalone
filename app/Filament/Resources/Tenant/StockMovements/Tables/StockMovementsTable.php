<?php

namespace App\Filament\Resources\Tenant\StockMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->searchable(),
                TextColumn::make('item.inventoryGroup.name')
                    ->label('مجموعة الصنف')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('المنتج')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable(),
                TextColumn::make('movement_type')
                    ->label('نوع الحركة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase' => 'شراء',
                        'purchase_reverse' => 'عكس شراء',
                        'transfer' => 'تحويل مخزني',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->label('تاريخ الإنشاء')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('تاريخ التحديث')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
