<?php

namespace App\Filament\Resources\Tenant\CustomerLevels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerLevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم فئة العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('points_required')
                    ->label('النقاط المطلوبة للترقية')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('discount_percent')
                    ->label('نسبة الخصم (%)')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 1).'%')
                    ->sortable(),

                TextColumn::make('applies_to')
                    ->label('الخصم يطبق على')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'play_only' => 'اللعب فقط',
                        'all' => 'اللعب وطلبات البوفيه معاً',
                        default => $state,
                    }),

                IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('points_required', 'asc')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
