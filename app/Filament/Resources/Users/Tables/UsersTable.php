<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('إسم المستخدم')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('الأدوار')
                    ->badge()
                    ->separator(',')
                    ->searchable(),
                TextColumn::make('permissions.name')
                    ->label('الصلاحيات')
                    ->badge()
                    ->separator(',')
                    ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('email_verified_at')
                //     ->dateTime()
                //     ->sortable(),
                // TextColumn::make('two_factor_confirmed_at')
                //     ->dateTime()
                //     ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإضافة')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('تاريخ التعديل')
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
