<?php

namespace App\Filament\Resources\Tenant\CashBoxes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class CashBoxesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الإسم')->searchable()->sortable(),
                TextColumn::make('code')->label('الرمز')->searchable()->sortable(),
                TextColumn::make('type')->label('النوع')->searchable()->sortable(),
                TextColumn::make('opening_balance')->label('الرصيد الافتتاحي')->money()->sortable(),
                TextColumn::make('current_balance')->label('الرصيد الحالي')->money()->sortable(),
                ToggleColumn::make('is_active')->label('نشط')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
