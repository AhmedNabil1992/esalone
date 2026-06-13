<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('shift_no')
                    ->label('رقم الشيفت'),
                TextEntry::make('shift_date')
                    ->label('تاريخ الشيفت')
                    ->date(),
                TextEntry::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextEntry::make('start_amount')
                    ->label('رصيد البداية')
                    ->money(),
                TextEntry::make('actual_amount')
                    ->label('الرصيد الفعلي')
                    ->money(),
            ]);
    }
}
