<?php

namespace App\Filament\Resources\Tenant\Purchases\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('reference')
                    ->label('المرجع')
                    ->placeholder('-'),
                TextEntry::make('shift.shift_no')
                    ->label('رقم الشيفت'),
                TextEntry::make('warehouse.name')
                    ->label('المخزن'),
                TextEntry::make('cashBox.name')
                    ->label('الخزنة'),
                TextEntry::make('supplier.name')
                    ->label('المورد')
                    ->placeholder('-'),
                TextEntry::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextEntry::make('total_amount')
                    ->label('الإجمالي')
                    ->money(),
                TextEntry::make('purchased_at')
                    ->label('تاريخ الشراء')
                    ->dateTime(),
                TextEntry::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('-'),
            ]);
    }
}
