<?php

namespace App\Filament\Resources\Tenant\PlaySessions\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderSessionDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderSessionDetails';

    protected static ?string $title = 'تفاصيل الطلبات';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
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
                    ->label('السعر')
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
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
