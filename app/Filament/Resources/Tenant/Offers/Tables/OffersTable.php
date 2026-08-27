<?php

namespace App\Filament\Resources\Tenant\Offers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('الفرع')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('اسم العرض')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('devices')
                    ->label('الأجهزة')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($state): ?string => $state),

                TextColumn::make('play_type')
                    ->label('نوع اللعب')
                    ->searchable()
                    ->placeholder('الكل'),

                TextColumn::make('period')
                    ->label('المدة')
                    ->formatStateUsing(fn ($state): string => $state ? $state.' دقيقة' : '—')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('السعر')
                    ->money()
                    ->sortable(),

                TextColumn::make('time_from')
                    ->label('من')
                    ->time(),

                TextColumn::make('time_to')
                    ->label('إلى')
                    ->time(),

                IconColumn::make('is_active')
                    ->label('مفعّل')
                    ->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
