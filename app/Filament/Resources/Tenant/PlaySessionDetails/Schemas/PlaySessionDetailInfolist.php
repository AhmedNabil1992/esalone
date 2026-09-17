<?php

namespace App\Filament\Resources\Tenant\PlaySessionDetails\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PlaySessionDetailInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('playSession.session_key')
                    ->label('رقم الجلسة')
                    ->placeholder('-'),
                TextEntry::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-'),
                TextEntry::make('play_type')
                    ->label('نوع اللعب')
                    ->placeholder('-'),
                TextEntry::make('play_category')
                    ->label('الفئة')
                    ->placeholder('-'),
                TextEntry::make('play_price')
                    ->label('سعر الساعة')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('period')
                    ->label('المدة (دقيقة)')
                    ->placeholder('-'),
                TextEntry::make('cost')
                    ->label('الإيراد')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('start_time')
                    ->label('بداية الوقت')
                    ->dateTime('Y-m-d h:i A'),
                TextEntry::make('end_time')
                    ->label('نهاية الوقت')
                    ->dateTime('Y-m-d h:i A')
                    ->placeholder('-'),
                TextEntry::make('shift_id')
                    ->label('رقم الشيفت')
                    ->placeholder('-'),
            ]);
    }
}
