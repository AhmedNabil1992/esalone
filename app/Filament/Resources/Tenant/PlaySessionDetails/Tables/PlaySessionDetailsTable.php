<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaySessionDetailsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('playSession.session_key')
                    ->label('رقم الجلسة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('play_type')
                    ->label('نوع اللعب')
                    ->placeholder('-'),
                TextColumn::make('play_category')
                    ->label('الفئة')
                    ->placeholder('-'),
                TextColumn::make('period')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('الإيراد')
                    ->money()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('بداية الوقت')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->label('نهاية الوقت')
                    ->dateTime()
                    ->placeholder('-'),
                TextColumn::make('shift_id')
                    ->label('رقم الشيفت')
                    ->placeholder('-'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
