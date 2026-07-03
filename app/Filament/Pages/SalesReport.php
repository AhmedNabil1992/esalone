<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'التقرير المالي والمبيعات';

    protected static ?string $navigationLabel = 'التقرير المالي والمبيعات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.sales-report';

    public function table(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->query(
                Invoice::query()
                    ->where('status', 'posted')
                    ->with(['customer', 'payments'])
                    ->latest('issued_at')
            )
            ->columns([
                TextColumn::make('issued_at')
                    ->label('تاريخ الفاتورة')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'device_sale' => 'primary',
                        'direct_sale' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'device_sale' => 'جلسة لعب',
                        'direct_sale' => 'بيع مباشر (بوفيه)',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('بدون عميل')
                    ->searchable(),
                TextColumn::make('play_amount')
                    ->label('مبلغ اللعب')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي اللعب'))
                    ->sortable(),
                TextColumn::make('order_amount')
                    ->label('مبيعات الطلبات')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي المبيعات'))
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('الضريبة')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الضرائب'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service_amount')
                    ->label('الخدمة')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الخدمة'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discount_amount')
                    ->label('الخصومات')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الخصومات'))
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('الصافي المطلوب')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('الصافي الكلي'))
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('cash_paid')
                    ->label('كاش محصل')
                    ->money('EGP')
                    ->state(function (Invoice $record): float {
                        return (float) $record->payments->where('payment_method', 'cash')->sum('amount');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('كاش كلي')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $invoiceIds = $query->clone()->pluck('id');

                                return (float) InvoicePayment::whereIn('invoice_id', $invoiceIds)
                                    ->where('payment_method', 'cash')
                                    ->sum('amount');
                            })
                    ),
                TextColumn::make('wallet_paid')
                    ->label('خصم محفظة')
                    ->money('EGP')
                    ->state(function (Invoice $record): float {
                        return (float) $record->payments->where('payment_method', 'customer_balance')->sum('amount');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('محفظة كلي')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $invoiceIds = $query->clone()->pluck('id');

                                return (float) InvoicePayment::whereIn('invoice_id', $invoiceIds)
                                    ->where('payment_method', 'customer_balance')
                                    ->sum('amount');
                            })
                    ),
                TextColumn::make('remain_amount')
                    ->label('المتبقي الآجل')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('آجل كلي'))
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('issued_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default(Carbon::now()->startOfMonth()),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default(Carbon::today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('issued_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'من تاريخ: '.Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'إلى تاريخ: '.Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                SelectFilter::make('invoice_type')
                    ->label('نوع الفاتورة')
                    ->options([
                        'device_sale' => 'جلسات ألعاب',
                        'direct_sale' => 'بيع مباشر (بوفيه)',
                    ]),
                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent);
    }
}
