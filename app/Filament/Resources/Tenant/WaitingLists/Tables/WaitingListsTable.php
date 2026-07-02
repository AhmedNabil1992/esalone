<?php

namespace App\Filament\Resources\Tenant\WaitingLists\Tables;

use App\Filament\Pages\DeviceControl;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WaitingListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer_name')
                    ->label('اسم العميل')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('customer_phone')
                    ->label('رقم الهاتف')
                    ->searchable(),

                TextColumn::make('devicesType.name')
                    ->label('نوع الجهاز المطلوب')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'waiting' => 'warning',
                        'playing' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'waiting' => 'في الانتظار',
                        'playing' => 'يلعب حالياً',
                        'cancelled' => 'ملغي',
                        default => $state,
                    })
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('وقت الدخول')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(40)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'waiting' => 'في الانتظار',
                        'playing' => 'يلعب حالياً',
                        'cancelled' => 'ملغي',
                    ]),
                SelectFilter::make('devices_type_id')
                    ->label('نوع الجهاز')
                    ->relationship('devicesType', 'name'),
            ])
            ->actions([
                Action::make('start_play')
                    ->label('بدء اللعب')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'waiting')
                    ->action(fn ($record) => $record->update(['status' => 'playing']))
                    ->url(fn () => DeviceControl::getUrl()),

                Action::make('cancel_turn')
                    ->label('إلغاء الدور')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'waiting')
                    ->action(fn ($record) => $record->update(['status' => 'cancelled']))
                    ->requiresConfirmation(),

                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
