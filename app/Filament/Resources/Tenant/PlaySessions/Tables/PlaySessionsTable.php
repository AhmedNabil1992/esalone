<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaySessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('prepaid_amount')
                    ->label('المبلغ المدفوع')
                    ->money(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'قيد الانتظار',
                        'completed' => 'مكتمل',
                        'canceled' => 'ملغي',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'canceled' => 'danger',
                        default => 'secondary',
                    })
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
            ->defaultGroup('device.type.name')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
