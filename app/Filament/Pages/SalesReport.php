<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
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
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'التقرير المالي والمبيعات';

    protected static ?string $navigationLabel = 'التقرير المالي والمبيعات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.sales-report';

    public function table(Table $table): Table
    {
        $clock = app(TenantClock::class);
        $defaultFrom = $clock->nowLocal()->startOfMonth()->toDateString();
        $defaultUntil = $clock->nowLocal()->toDateString();

        return $table
            ->query(
                Invoice::query()
                    ->where('status', 'posted')
                    ->with(['appointment', 'customer', 'payments'])
                    ->latest('issued_at')
            )
            ->columns([
                TextColumn::make('issued_at')
                    ->label('تاريخ الفاتورة')
                    ->dateTime('Y-m-d h:i A')
                    ->sortable(),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_type')
                    ->label('مصدر الفاتورة')
                    ->badge()
                    ->state(fn (Invoice $record): string => $record->appointment
                        ? 'appointment'
                        : ($record->invoice_type === 'direct_sale' ? 'direct_sale' : 'other'))
                    ->color(fn (string $state): string => match ($state) {
                        'appointment' => 'primary',
                        'direct_sale' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'appointment' => 'تحصيل موعد',
                        'direct_sale' => 'بيع منتجات',
                        default => 'مبيعات أخرى',
                    })
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('العميل')
                    ->placeholder('بدون عميل')
                    ->searchable(),
                TextColumn::make('subtotal')
                    ->label('الإجمالي قبل الخصم')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('الإجمالي قبل الخصم'))
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('الضريبة')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الضرائب'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service_amount')
                    ->label('رسوم إضافية')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الرسوم الإضافية'))
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
                            ->default($defaultFrom),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($date)),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($date)),
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
                SelectFilter::make('invoice_source')
                    ->label('مصدر الفاتورة')
                    ->options([
                        'appointment' => 'تحصيل موعد',
                        'direct_sale' => 'بيع منتجات',
                        'other' => 'مبيعات أخرى',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'appointment' => $query->whereHas('appointment'),
                            'direct_sale' => $query->whereDoesntHave('appointment')
                                ->where('invoice_type', 'direct_sale'),
                            'other' => $query->whereDoesntHave('appointment')
                                ->where(fn (Builder $query): Builder => $query
                                    ->whereNull('invoice_type')
                                    ->orWhere('invoice_type', '!=', 'direct_sale')),
                            default => $query,
                        };
                    }),
                SelectFilter::make('customer_id')
                    ->label('العميل')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent);
    }
}
