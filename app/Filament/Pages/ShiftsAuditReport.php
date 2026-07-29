<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Shift;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftsAuditReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'تقرير عجز وزيادة الورديات';

    protected static ?string $navigationLabel = 'تقرير عجز وزيادة الورديات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.shifts-audit-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Shift::query()
                    ->where('status', 'close')
                    ->with(['opener', 'closer'])
                    ->latest('shift_date')
            )
            ->columns([
                TextColumn::make('shift_no')
                    ->label('رقم الشيفت')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('shift_date')
                    ->label('تاريخ الشيفت')
                    ->date()
                    ->sortable(),
                TextColumn::make('opener.name')
                    ->label('فتح بواسطة')
                    ->placeholder('غير معروف'),
                TextColumn::make('closer.name')
                    ->label('أغلق بواسطة')
                    ->placeholder('غير معروف'),
                TextColumn::make('start_amount')
                    ->label('رصيد البداية')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي أرصدة البداية')),
                TextColumn::make('expected_cash')
                    ->label('الرصيد المتوقع')
                    ->money('EGP')
                    ->state(function (Shift $record): float {
                        return (float) $record->start_amount
                            + (float) $record->playstation_amount
                            + (float) $record->sales_amount
                            + (float) $record->tax_amount
                            + (float) $record->service_amount
                            + (float) $record->customer_add_amount
                            + (float) $record->income_history_amount
                            - (float) $record->purchase_amount
                            - (float) $record->expenses_amount
                            - (float) $record->discount_amount
                            - (float) $record->customer_credit_amount
                            - (float) $record->customer_minus_amount
                            + (float) $record->credit_amount;
                    }),
                TextColumn::make('actual_amount')
                    ->label('الرصيد الفعلي')
                    ->money('EGP')
                    ->summarize(Sum::make()->label('إجمالي الأرصدة الفعلية')),
                TextColumn::make('discrepancy')
                    ->label('حالة التسوية والفرق')
                    ->state(function (Shift $record): float {
                        $expected = (float) $record->start_amount
                            + (float) $record->playstation_amount
                            + (float) $record->sales_amount
                            + (float) $record->tax_amount
                            + (float) $record->service_amount
                            + (float) $record->customer_add_amount
                            + (float) $record->income_history_amount
                            - (float) $record->purchase_amount
                            - (float) $record->expenses_amount
                            - (float) $record->discount_amount
                            - (float) $record->customer_credit_amount
                            - (float) $record->customer_minus_amount
                            + (float) $record->credit_amount;

                        return (float) $record->actual_amount - $expected;
                    })
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state < 0.0 => 'danger',
                        $state > 0.0 => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (float $state): string {
                        if ($state < 0.0) {
                            return 'عجز '.number_format(abs($state), 2).' ج.م';
                        }
                        if ($state > 0.0) {
                            return 'زيادة '.number_format($state, 2).' ج.م';
                        }

                        return 'مطابق تماماً';
                    }),
                TextColumn::make('notes')
                    ->label('ملاحظات التسليم')
                    ->placeholder('لا توجد ملاحظات')
                    ->limit(30)
                    ->tooltip(fn (Shift $record): ?string => $record->notes),
            ])
            ->filters([
                Filter::make('shift_date')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('shift_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('shift_date', '<=', $date),
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
                Filter::make('has_discrepancy')
                    ->label('الورديات غير المطابقة فقط')
                    ->query(function (Builder $query): Builder {
                        return $query->whereRaw('actual_amount <> (start_amount + playstation_amount + sales_amount + tax_amount + service_amount + customer_add_amount + income_history_amount - purchase_amount - expenses_amount - discount_amount - customer_credit_amount - customer_minus_amount + credit_amount)');
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}
