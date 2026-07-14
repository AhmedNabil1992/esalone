<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CatalogItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('إسم الصنف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->money(),
                TextColumn::make('sale_price')
                    ->label('سعر البيع')
                    ->money(),
                TextColumn::make('min_quantity')
                    ->label('حد الأمان')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                ToggleColumn::make('is_product')
                    ->label('هل هذا صنف منتج؟')
                    ->onColor('success')
                    ->offColor('danger'),
                ToggleColumn::make('is_component')
                    ->label('هل هذا صنف مكون؟')
                    ->onColor('success')
                    ->offColor('danger'),
                ToggleColumn::make('is_active')
                    ->label('نشط؟')
                    ->onColor('success')
                    ->offColor('danger'),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
