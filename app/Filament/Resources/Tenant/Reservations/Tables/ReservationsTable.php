<?php

namespace App\Filament\Resources\Tenant\Reservations\Tables;

use App\Models\Tenant\SystemConfig;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('playPrice.description')
                    ->label('فئة السعر')
                    ->formatStateUsing(fn ($record) => "{$record->playPrice?->playType?->name} - {$record->playPrice?->description} ({$record->playPrice?->price} ج.م)")
                    ->searchable(),
                TextColumn::make('start_time')
                    ->label('وقت البدء')
                    ->dateTime('Y-m-d h:i A')
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'UTC'))
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('وقت الانتهاء')
                    ->dateTime('Y-m-d h:i A')
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'UTC'))
                    ->sortable(),
                TextColumn::make('duration_minutes')
                    ->label('المدة (دقائق)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('device_id')
                    ->label('الجهاز')
                    ->relationship('device', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                    ]),
            ])
            ->recordActions([
                Action::make('complete')
                    ->label('إكمال')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(fn ($record) => $record->update(['status' => 'completed'])),
                Action::make('cancel')
                    ->label('إلغاء')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'active')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['status' => 'cancelled'])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
