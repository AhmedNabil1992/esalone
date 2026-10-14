<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\Schemas;

use App\Support\Tenant\TenantClock;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('بيانات الفاتورة')
                    ->schema([
                        TextEntry::make('invoice_no')
                            ->label('رقم الفاتورة')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('issued_at')
                            ->label('التاريخ')
                            ->dateTime('Y-m-d h:i A')
                            ->timezone(app(TenantClock::class)->timezone()),
                        TextEntry::make('status')
                            ->label('الحالة')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'posted' ? 'success' : 'gray'),
                        TextEntry::make('invoice_type')
                            ->label('نوع الفاتورة')
                            ->badge()
                            ->color(fn (string $state): string => $state === 'direct_sale' ? 'success' : 'primary')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'direct_sale' => 'بيع مباشر',
                                'device_sale' => 'جهاز',
                                default => '-',
                            }),
                        TextEntry::make('device.name')
                            ->label('الجهاز')
                            ->placeholder('-'),
                        TextEntry::make('customer.name')
                            ->label('العميل')
                            ->placeholder('-'),
                        TextEntry::make('customer.phone')
                            ->label('هاتف العميل')
                            ->placeholder('-'),
                        TextEntry::make('cashBox.name')
                            ->label('الخزينة')
                            ->placeholder('-'),
                        TextEntry::make('shift.shift_no')
                            ->label('الشيفت')
                            ->placeholder('-'),
                        TextEntry::make('createdBy.name')
                            ->label('المستخدم')
                            ->placeholder('-'),
                    ])
                    ->columns(4),

                Section::make('ملخص المبالغ')
                    ->schema([
                        TextEntry::make('play_amount')
                            ->label('قيمة اللعب')
                            ->money()
                            ->color('success'),
                        TextEntry::make('order_amount')
                            ->label('قيمة الطلبات')
                            ->money()
                            ->color('success'),
                        TextEntry::make('subtotal')
                            ->label('الإجمالي قبل الخصم')
                            ->money()
                            ->weight('bold'),
                        TextEntry::make('discount_amount')
                            ->label('الخصم')
                            ->money()
                            ->color('danger'),
                        TextEntry::make('discount_reason')
                            ->label('سبب الخصم')
                            ->placeholder('-')
                            ->columnSpan(2),
                        TextEntry::make('service_amount')
                            ->label('الخدمة')
                            ->money(),
                        TextEntry::make('tax_amount')
                            ->label('الضريبة')
                            ->money(),
                        TextEntry::make('deposit_amount')
                            ->label('المقدم')
                            ->money(),
                        TextEntry::make('final_amount')
                            ->label('الصافي المطلوب')
                            ->money()
                            ->weight('bold')
                            ->color('primary'),
                        TextEntry::make('paid_amount')
                            ->label('المدفوع')
                            ->money()
                            ->color('success'),
                        TextEntry::make('remain_amount')
                            ->label('المتبقي / الرصيد')
                            ->money()
                            ->weight('bold')
                            ->color(fn (string $state): string => (float) $state < 0 ? 'danger' : 'success'),
                    ])
                    ->columns(4),
            ]);
    }
}
