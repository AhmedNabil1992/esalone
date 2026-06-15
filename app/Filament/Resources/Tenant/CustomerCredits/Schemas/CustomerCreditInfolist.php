<?php

namespace App\Filament\Resources\Tenant\CustomerCredits\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerCreditInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('customer.name')->label('العميل'),
            TextEntry::make('type')
                ->label('نوع العملية')
                ->formatStateUsing(fn ($state) => $state === 'deposit' ? 'إيداع' : 'سحب')
                ->badge()
                ->color(fn ($state) => $state === 'deposit' ? 'success' : 'danger'),
            TextEntry::make('amount')->label('المبلغ')->money(),
            TextEntry::make('balance_before')->label('الرصيد قبل')->money(),
            TextEntry::make('balance_after')->label('الرصيد بعد')->money(),
            TextEntry::make('description')->label('ملاحظة'),
            TextEntry::make('user.name')->label('بواسطة'),
            TextEntry::make('created_at')->label('التاريخ')->dateTime(),
        ]);
    }
}
