<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlayPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('playType.name')
                    ->label('نوع اللعب')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('سعر اللعب')
                    ->money()
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_from')
                    ->label('وقت بدء السعر')
                    ->time('h:i A')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->defaultGroup('device.name')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
