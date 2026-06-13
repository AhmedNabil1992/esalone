<?php

namespace App\Filament\Resources\Tenant\InventoryGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class InventoryGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('إسم المجموعة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->searchable()
                    ->sortable(),
                ImageColumn::make('picture')
                    ->label('صورة المجموعة')
                    ->getStateUsing(static fn ($record): ?string => filled($record->picture) ? tenant_asset($record->picture) : null)
                    ->square(),
                ToggleColumn::make('is_active')
                    ->label('نشطة')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),
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
