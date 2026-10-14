<?php

namespace App\Filament\Resources\Tenant\Appointments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class AppointmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('start_at')
                    ->label('موعد الحضور')
                    ->dateTime('Y-m-d h:i A'),
                TextEntry::make('end_at')
                    ->label('وقت الانتهاء المتوقع')
                    ->dateTime('Y-m-d h:i A'),
                TextEntry::make('customer.name')
                    ->label('العميل')
                    ->placeholder('بدون تسجيل'),
                TextEntry::make('contact_name')
                    ->label('اسم صاحب طلب الحجز')
                    ->placeholder('—'),
                TextEntry::make('contact_phone')
                    ->label('رقم التواصل')
                    ->placeholder('—'),
                TextEntry::make('staffMember.name')
                    ->label('الموظف'),
                TextEntry::make('items.service_name')
                    ->label('الخدمات')
                    ->listWithLineBreaks(),
                TextEntry::make('duration_minutes')
                    ->label('المدة بالدقائق'),
                TextEntry::make('total_price')
                    ->label('الإجمالي')
                    ->money('EGP'),
                TextEntry::make('status')
                    ->label('الحالة'),
                TextEntry::make('invoice.invoice_no')
                    ->label('الفاتورة')
                    ->placeholder('لم يتم التحصيل'),
                TextEntry::make('notes')
                    ->label('ملاحظات')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}
