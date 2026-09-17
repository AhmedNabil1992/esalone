<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenant\Invoices\Tables;

use App\Support\Tenant\TenantClock;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'direct_sale' => 'بيع مباشر',
                        'device_sale' => 'جهاز',
                        default => '-',
                    })
                    ->color(fn (?string $state): string => $state === 'direct_sale' ? 'success' : 'primary'),
                TextColumn::make('issued_at')
                    ->label('التاريخ')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('device.name')
                    ->label('الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('customer.phone')
                    ->label('الهاتف')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('cashBox.name')
                    ->label('الخزينة')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('shift.shift_no')
                    ->label('الشيفت')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('الصافي')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي الصافي')->money(),
                        Count::make()->label('عدد الفواتير'),
                    ]),
                TextColumn::make('paid_amount')
                    ->label('المدفوع')
                    ->money()
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي المدفوع')->money(),
                    ]),
                TextColumn::make('remain_amount')
                    ->label('المتبقي')
                    ->money()
                    ->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('إجمالي المتبقي')->money(),
                    ]),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'paid' => 'مدفوعة',
                        'unpaid' => 'غير مدفوعة',
                        'partially_paid' => 'مدفوعة جزئياً',
                        'posted' => 'مكتملة',
                        'draft' => 'مسودة',
                        'cancelled', 'voided' => 'ملغاة',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'paid', 'posted' => 'success',
                        'partially_paid' => 'warning',
                        'unpaid', 'cancelled', 'voided' => 'danger',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('issued_at')
                    ->label('التاريخ')
                    ->form([
                        DatePicker::make('from')->label('من تاريخ')->default($defaultFrom),
                        DatePicker::make('until')->label('إلى تاريخ')->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data) use ($defaultFrom, $defaultUntil): Builder {
                        $from = $data['from'] ?? $defaultFrom;
                        $until = $data['until'] ?? $defaultUntil;

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($from))
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($until))
                            );
                    }),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'paid' => 'مدفوعة',
                        'unpaid' => 'غير مدفوعة',
                        'partially_paid' => 'مدفوعة جزئياً',
                        'posted' => 'مكتملة',
                        'draft' => 'مسودة',
                        'cancelled' => 'ملغاة',
                    ]),
                SelectFilter::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->options([
                        'direct_sale' => 'بيع مباشر',
                        'device_sale' => 'جهاز',
                    ]),
                SelectFilter::make('shift_id')
                    ->label('رقم الشيفت')
                    ->relationship('shift', 'shift_no', fn (Builder $query) => $query->orderByDesc('shift_no'))
                    ->searchable()
                    ->preload(),
                SelectFilter::make('cash_box_id')
                    ->label('الخزينة')
                    ->relationship('cashBox', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('device_id')
                    ->label('الجهاز')
                    ->relationship('device', 'name')
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
}
