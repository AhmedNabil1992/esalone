<?php

namespace App\Filament\Resources\Tenant\PlayTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlayTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deviceType.name')
                    ->label('إسم نوع الجهاز')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('إسم نوع اللعب')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('وصف نوع اللعب')
                    ->searchable(),
                ImageColumn::make('picture')
                    ->label('صورة نوع اللعب')
                    // ->disk('public')
                    ->getStateUsing(static fn ($record): ?string => filled($record->picture) ? tenant_asset($record->picture) : null)
                    ->visibility('public'),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d h:i A')
                    ->label('تاريخ الإنشاء')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('Y-m-d h:i A')
                    ->label('تاريخ التحديث')
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
