<?php

namespace App\Filament\Resources\Tenant\Warehouses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class WarehousesTable
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
                    ->label('إسم المخزن')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('code')
                    ->label('كود المخزن')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->label('عنوان المخزن')
                    ->searchable()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('نشط')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),
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
