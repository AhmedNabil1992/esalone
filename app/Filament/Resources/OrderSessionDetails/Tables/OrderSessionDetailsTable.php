<?php

namespace App\Filament\Resources\OrderSessionDetails\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderSessionDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('اسم الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('catalogItem.type.name')
                    ->label('نوع المنتج')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('catalogItem.name')
                    ->label('اسم المنتج')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('سعر المنتج')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->placeholder('-'),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->placeholder('-'),
                TextColumn::make('order_by')
                    ->label('تم الطلب بواسطة')
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-'),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-'),
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
