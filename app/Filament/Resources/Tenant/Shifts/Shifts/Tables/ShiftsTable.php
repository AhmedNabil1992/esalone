<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Tables;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('shift_no')
                    ->label('رقم الشيفت')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift_date')
                    ->label('تاريخ الشيفت')
                    ->date()
                    ->sortable(),
                TextColumn::make('shift_from')
                    ->label('من')
                    ->dateTime(),
                TextColumn::make('shift_to')
                    ->label('إلى')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('start_amount')
                    ->label('رصيد البداية')
                    ->money(),
                TextColumn::make('actual_amount')
                    ->label('الرصيد الفعلي')
                    ->money(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                ToggleColumn::make('is_active')
                    ->label('نشط'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('close')
                    ->label('تقفيل الوردية')
                    ->color('danger')
                    ->icon('heroicon-o-lock-closed')
                    ->url(fn ($record) => ShiftResource::getUrl('close', ['record' => $record]))
                    ->visible(fn ($record) => $record->status === 'open' && $record->is_active),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
