<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant\MarketHistory;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketHistoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'سجل المبيعات';

    protected static ?string $navigationLabel = 'سجل المبيعات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.market-history-report';

    public function table(Table $table): Table
    {
        $defaultFrom = Carbon::now()->startOfMonth()->toDateString();
        $defaultUntil = Carbon::today()->toDateString();

        return $table
            ->query(MarketHistory::query()->with(['item', 'device.type', 'customer'])->latest('id'))
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('transaction_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                TextColumn::make('transaction_time')
                    ->label('الوقت')
                    ->searchable(),
                TextColumn::make('invoice_no')
                    ->label('رقم الفاتورة')
                    ->searchable()
                    ->sortable(),
                // TextColumn::make('customer_id')
                //     ->label('ID العميل')
                //     ->placeholder('-')
                //     ->sortable(),
                TextColumn::make('customer.name')
                    ->label('اسم العميل')
                    ->placeholder('-')
                    ->searchable(),
                // TextColumn::make('device_id')
                //     ->label('ID الجهاز')
                //     ->placeholder('-')
                //     ->sortable(),
                TextColumn::make('device.type.name')
                    ->label('نوع الجهاز')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('device.name')
                    ->label('اسم الجهاز')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('subject_name')
                    ->label('اسم مرجعي')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('الصنف')
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('السعر')
                    ->money()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('القيمة')
                    ->money()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label('بواسطة')
                    ->searchable(),
                TextColumn::make('shift_no')
                    ->label('الشيفت')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('transaction_date')
                    ->label('تاريخ المبيعات')
                    ->form([
                        DatePicker::make('from')
                            ->label('من تاريخ')
                            ->default($defaultFrom),
                        DatePicker::make('until')
                            ->label('إلى تاريخ')
                            ->default($defaultUntil),
                    ])
                    ->query(function (Builder $query, array $data) use ($defaultFrom, $defaultUntil): Builder {
                        $from = $data['from'] ?? $defaultFrom;
                        $until = $data['until'] ?? $defaultUntil;

                        return $query
                            ->when(
                                filled($from),
                                fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '>=', $from)
                            )
                            ->when(
                                filled($until),
                                fn (Builder $builder): Builder => $builder->whereDate('transaction_date', '<=', $until)
                            );
                    }),
            ])
            ->defaultSort('id', 'desc');
    }
}
