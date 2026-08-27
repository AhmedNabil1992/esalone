<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\CashBoxLedgerEntries\Tables;

use App\Models\Tenant\CashBoxLedgerEntry;
use Carbon\Carbon;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashBoxLedgerEntriesTable
{
    public static function configure(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->columns([
                TextColumn::make('cashBox.name')
                    ->label('الخزنة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('direction')
                    ->label('الاتجاه')
                    ->formatStateUsing(fn (string $state): string => $state === 'in' ? 'دخول' : 'خروج')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'in' ? 'success' : 'danger'),
                TextColumn::make('operation')
                    ->label('نوع العملية')
                    ->formatStateUsing(fn (?string $state): string => self::formatOperation($state))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'direct_sale', 'play_invoice_payment', 'customer_credit_deposit' => 'success',
                        'purchase', 'expense', 'customer_credit_withdraw', 'owner_withdrawal' => 'danger',
                        'customer_credit_payment', 'invoice_unpaid_debt', 'transfer' => 'info',
                        'purchase_reverse', 'shift_deficit', 'shift_surplus' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('reference_type')
                    ->label('مرجع العملية')
                    ->formatStateUsing(fn (?string $state, CashBoxLedgerEntry $record): string => self::formatReference($state, $record->reference_id))
                    ->placeholder('-'),
                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money()
                    ->sortable(),
                TextColumn::make('shift.shift_no')
                    ->label('رقم الشيفت')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('createdBy.name')
                    ->label('بواسطة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('الوصف')
                    ->placeholder('-')
                    ->limit(40),
                TextColumn::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('occurred_at')
                    ->label('تاريخ العملية')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default($defaultFrom),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($defaultUntil),
                    ])
                    ->default([
                        'from' => $defaultFrom,
                        'until' => $defaultUntil,
                    ])
                    ->query(function (Builder $query, array $data) use ($defaultFrom, $defaultUntil): Builder {
                        $from = $data['from'] ?? $defaultFrom;
                        $until = $data['until'] ?? $defaultUntil;

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->whereDate('occurred_at', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('occurred_at', '<=', $until)
                            );
                    }),
                SelectFilter::make('cash_box_id')
                    ->label('الخزنة')
                    ->relationship('cashBox', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('direction')
                    ->label('الاتجاه')
                    ->options([
                        'in' => 'دخول',
                        'out' => 'خروج',
                    ]),
                SelectFilter::make('operation')
                    ->label('نوع العملية')
                    ->options([
                        'direct_sale' => 'بيع مباشر',
                        'customer_credit_payment' => 'سداد برصيد المحفظة',
                        'invoice_unpaid_debt' => 'آجل غير مدفوع (دين)',
                        'purchase' => 'شراء',
                        'purchase_reverse' => 'عكس شراء',
                        'expense' => 'مصروف',
                        'customer_credit_deposit' => 'إيداع رصيد عميل',
                        'customer_credit_withdraw' => 'سحب رصيد عميل',
                        'play_invoice_play' => 'فاتورة لعب - لعب',
                        'play_invoice_orders' => 'فاتورة لعب - طلبات',
                        'play_invoice_tax' => 'فاتورة لعب - ضريبة',
                        'play_invoice_service' => 'فاتورة لعب - خدمة',
                        'play_invoice_discount' => 'فاتورة لعب - خصم',
                        'play_invoice_payment' => 'فاتورة لعب - تحصيل',
                        'adjustment' => 'تسوية',
                        'cashbox_adjustment' => 'تسوية رصيد خزانة',
                        'owner_withdrawal' => 'مسحوبات مالك (سحب)',
                        'shift_deficit' => 'عجز إيراد / وردية',
                        'shift_surplus' => 'زيادة إيراد / وردية',
                        'transfer' => 'تحويل',
                    ]),
                SelectFilter::make('shift_id')
                    ->label('رقم الشيفت')
                    ->relationship('shift', 'shift_no', fn (Builder $query) => $query->orderByDesc('shift_no'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('created_by_user_id')
                    ->label('بواسطة')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->pluralModelLabel('الصفحات')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function formatOperation(?string $operation): string
    {
        if (! $operation) {
            return '-';
        }

        return match ($operation) {
            'direct_sale' => 'بيع مباشر',
            'direct_sale_discount' => 'خصم بيع مباشر',
            'customer_credit_payment' => 'سداد برصيد المحفظة',
            'invoice_unpaid_debt' => 'آجل غير مدفوع (دين)',
            'purchase' => 'شراء',
            'purchase_reverse' => 'عكس شراء',
            'expense' => 'مصروف',
            'customer_credit_deposit' => 'إيداع رصيد عميل',
            'customer_credit_withdraw' => 'سحب رصيد عميل',
            'play_invoice_play' => 'فاتورة لعب - لعب',
            'play_invoice_orders' => 'فاتورة لعب - طلبات',
            'play_invoice_tax' => 'فاتورة لعب - ضريبة',
            'play_invoice_service' => 'فاتورة لعب - خدمة',
            'play_invoice_discount' => 'فاتورة لعب - خصم',
            'play_invoice_payment' => 'فاتورة لعب - تحصيل',
            'adjustment' => 'تسوية',
            'cashbox_adjustment' => 'تسوية رصيد خزانة',
            'owner_withdrawal' => 'مسحوبات مالك (سحب)',
            'shift_deficit' => 'عجز إيراد / وردية',
            'shift_surplus' => 'زيادة إيراد / وردية',
            'transfer' => 'تحويل',
            default => $operation,
        };
    }

    private static function formatReference(?string $referenceType, ?int $referenceId): string
    {
        if (! $referenceType || ! $referenceId) {
            return '-';
        }

        $normalizedReferenceType = str_replace('/', '\\', ltrim($referenceType, '\\'));
        $referenceBaseName = strtolower(class_basename($normalizedReferenceType));

        $referenceLabel = match ($referenceBaseName) {
            'purchase' => 'شراء',
            'expensehistory' => 'مصروف',
            'customercredit' => 'رصيد عميل',
            'invoice' => 'فاتورة',
            'invoicepayment' => 'دفعة فاتورة',
            'cashbox' => 'خزنة',
            'cashboxledgerentry' => 'قيد خزنة',
            'stockmovement' => 'حركة مخزون',
            default => 'مرجع',
        };

        return sprintf('%s #%d', $referenceLabel, $referenceId);
    }
}
