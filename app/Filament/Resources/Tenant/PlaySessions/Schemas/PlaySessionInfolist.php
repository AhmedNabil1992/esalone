<?php

namespace App\Filament\Resources\Tenant\PlaySessions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PlaySessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-'),
                TextEntry::make('device.name')
                    ->label('إسم الجهاز')
                    ->placeholder('-'),
                TextEntry::make('customer.name')
                    ->label('إسم العميل')
                    ->placeholder('-'),
                TextEntry::make('prepaid_amount')
                    ->label('المبلغ المدفوع مسبقاً')
                    ->money()
                    ->placeholder('-'),
            ]);
    }
}
