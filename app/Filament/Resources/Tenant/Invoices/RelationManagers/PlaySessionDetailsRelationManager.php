<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Support\Tenant\TenantClock;

class PlaySessionDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'playSessionDetails';

    protected static ?string $title = 'تفاصيل اللعب';

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
                TextColumn::make('hour_price')
                    ->label('سعر الساعة')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('play_time')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-'),
                TextColumn::make('cost')
                    ->label('القيمة')
                    ->money()
                    ->placeholder('-'),
                TextColumn::make('start_time')
                    ->label('بداية الوقت')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-'),
                TextColumn::make('end_time')
                    ->label('نهاية الوقت')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-')
                    ->timezone(app(TenantClock::class)->timezone()),
                TextColumn::make('username')
                    ->label('بواسطة')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->defaultSort('id', 'asc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
