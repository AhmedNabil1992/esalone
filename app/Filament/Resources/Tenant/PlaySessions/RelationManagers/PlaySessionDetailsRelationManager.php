<?php

namespace App\Filament\Resources\Tenant\PlaySessions\RelationManagers;

use App\Models\Tenant\SystemConfig;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlaySessionDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';

    protected static ?string $title = 'تفاصيل جلسة اللعب';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('اسم الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('play_type')
                    ->label('نوع اللعب')
                    ->placeholder('-'),
                TextColumn::make('play_category')
                    ->label('الفئة')
                    ->placeholder('-'),
                TextColumn::make('play_price')
                    ->label('سعر اللعب')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('period')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('cost')
                    ->label('الإيراد')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('start_time')
                    ->label('بداية الوقت')
                    ->dateTime()
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'UTC'))
                    ->placeholder('-'),
                TextColumn::make('end_time')
                    ->label('نهاية الوقت')
                    ->dateTime()
                    ->timezone(SystemConfig::get('sys_system_time_zone', 'UTC'))
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->placeholder('-'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
