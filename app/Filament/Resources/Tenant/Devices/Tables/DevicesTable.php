<?php

namespace App\Filament\Resources\Tenant\Devices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class DevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type.name')
                    ->label('نوع الجهاز')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('إسم الجهاز')
                    ->searchable(),
                TextColumn::make('ip_address')
                    ->label('عنوان IP')
                    ->searchable(),
                TextColumn::make('mac_address')
                    ->label('عنوان MAC')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('limit_time')
                    ->label('الوقت المحدد')
                    ->searchable(),
                ToggleColumn::make('is_time')
                    ->label('يعتمد على الوقت')
                    ->onColor('success')
                    ->offColor('danger'),
                ToggleColumn::make('is_active')
                    ->label('مفعل')
                    ->onColor('success')
                    ->offColor('danger'),
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
            ->defaultGroup('type.name')
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
