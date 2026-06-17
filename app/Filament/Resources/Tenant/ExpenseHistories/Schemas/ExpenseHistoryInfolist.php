<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ExpenseHistoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('cashBox.name')
                    ->label('الخزنة'),
                TextEntry::make('amount')
                    ->label('قيمة المصروف')
                    ->money(),
                TextEntry::make('description')
                    ->label('الوصف')
                    ->placeholder('-'),
                TextEntry::make('affects_profit')
                    ->label('يؤثر على الربحية')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'نعم' : 'لا'),
                TextEntry::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-'),
                TextEntry::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime(),
            ]);
    }
}
