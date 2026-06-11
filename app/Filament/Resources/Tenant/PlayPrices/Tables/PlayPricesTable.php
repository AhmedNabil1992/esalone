<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;


class PlayPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('playType.name')
                    ->label('نوع اللعب')
                    ->searchable(),
                TextColumn::make('devices.name')
                    ->label('الأجهزة')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable(),
                TextColumn::make('price')
                    ->label('سعر اللعب')
                    ->money()
                    ->searchable(),
                TextColumn::make('start_from')
                    ->label('وقت بدء السعر')
                    ->time(),
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
