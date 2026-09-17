<?php

namespace App\Filament\Resources\Tenant\CatalogItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
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
                    ->money()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sale_price')
                    ->label('السعر الافتراضي')
                    ->money()
                    ->sortable(),
                TextColumn::make('direct_sale_price')
                    ->label('سعر البيع المباشر')
                    ->getStateUsing(fn ($record) => $record->getEffectiveSalePrice(null, 'direct_sale'))
                    ->money()
                    ->sortable(),
                TextColumn::make('device_sale_price')
                    ->label('سعر الأجهزة')
                    ->getStateUsing(fn ($record) => $record->getEffectiveSalePrice(null, 'device_sale'))
                    ->money()
                    ->sortable(),
                TextColumn::make('table_sale_price')
                    ->label('سعر الترابيزات')
                    ->getStateUsing(fn ($record) => $record->getEffectiveSalePrice(null, 'table_sale'))
                    ->money()
                    ->sortable(),
                TextColumn::make('min_quantity')
                    ->label('حد الأمان')
                    ->formatStateUsing(fn ($state) => (float) $state)
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
                    ->dateTime('Y-m-d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التحديث')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_product')
                    ->label('فلترة بالمنتجات')
                    ->placeholder('الكل')
                    ->trueLabel('المنتجات فقط')
                    ->falseLabel('غير المنتجات'),
                TernaryFilter::make('is_component')
                    ->label('فلترة بالمكونات')
                    ->placeholder('الكل')
                    ->trueLabel('المكونات فقط')
                    ->falseLabel('غير المكونات'),
                TernaryFilter::make('is_active')
                    ->label('الحالة (نشط/غير نشط)')
                    ->placeholder('الكل')
                    ->trueLabel('نشط فقط')
                    ->falseLabel('غير نشط فقط'),
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
