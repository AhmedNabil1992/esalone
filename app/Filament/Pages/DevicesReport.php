<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Devices;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\PlayHistory;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class DevicesReport extends Page implements HasTable
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'تقرير إنتاجية واستخدام الأجهزة';

    protected static ?string $navigationLabel = 'تقرير إنتاجية واستخدام الأجهزة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.devices-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(Devices::query()->with('type'))
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الجهاز')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type.name')
                    ->label('نوع الجهاز'),
                TextColumn::make('sessions_count')
                    ->label('عدد الجلسات')
                    ->state(function (Devices $record): int {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = PlayHistory::where('device_id', $record->id);
                        if ($from) {
                            $query->whereDate('trx_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('trx_date', '<=', $until);
                        }

                        return (int) $query->count();
                    })
                    ->alignCenter(),
                TextColumn::make('total_hours')
                    ->label('إجمالي الساعات وملعب')
                    ->state(function (Devices $record): string {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = PlayHistory::where('device_id', $record->id);
                        if ($from) {
                            $query->whereDate('trx_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('trx_date', '<=', $until);
                        }

                        $minutes = (int) $query->sum('play_time');
                        $hours = floor($minutes / 60);
                        $remainingMin = $minutes % 60;

                        return "{$hours} س و {$remainingMin} د";
                    })
                    ->alignCenter(),
                TextColumn::make('total_play_amount')
                    ->label('إيراد اللعب')
                    ->money('EGP')
                    ->state(function (Devices $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = PlayHistory::where('device_id', $record->id);
                        if ($from) {
                            $query->whereDate('trx_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('trx_date', '<=', $until);
                        }

                        return (float) $query->sum('cost');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي إيراد اللعب')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $deviceIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $playQuery = PlayHistory::whereIn('device_id', $deviceIds);
                                if ($from) {
                                    $playQuery->whereDate('trx_date', '>=', $from);
                                }
                                if ($until) {
                                    $playQuery->whereDate('trx_date', '<=', $until);
                                }

                                return (float) $playQuery->sum('cost');
                            })
                    )
                    ->sortable(),
                TextColumn::make('total_orders_amount')
                    ->label('مبيعات بوفيه الجهاز')
                    ->money('EGP')
                    ->state(function (Devices $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = Invoice::where('device_id', $record->id)->where('status', 'posted');
                        if ($from) {
                            $query->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($from));
                        }
                        if ($until) {
                            $query->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($until));
                        }

                        return (float) $query->sum('order_amount');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي مبيعات الطلبات')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $deviceIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $invoiceQuery = Invoice::whereIn('device_id', $deviceIds)->where('status', 'posted');
                                if ($from) {
                                    $invoiceQuery->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($from));
                                }
                                if ($until) {
                                    $invoiceQuery->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($until));
                                }

                                return (float) $invoiceQuery->sum('order_amount');
                            })
                    )
                    ->sortable(),
                TextColumn::make('total_revenue')
                    ->label('إجمالي الدخل الكلي')
                    ->money('EGP')
                    ->state(function (Devices $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $playQuery = PlayHistory::where('device_id', $record->id);
                        $invoiceQuery = Invoice::where('device_id', $record->id)->where('status', 'posted');

                        if ($from) {
                            $playQuery->whereDate('trx_date', '>=', $from);
                            $invoiceQuery->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($from));
                        }
                        if ($until) {
                            $playQuery->whereDate('trx_date', '<=', $until);
                            $invoiceQuery->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($until));
                        }

                        $play = (float) $playQuery->sum('cost');
                        $orders = (float) $invoiceQuery->sum('order_amount');

                        return $play + $orders;
                    })
                    ->weight('bold')
                    ->summarize(
                        Summarizer::make()
                            ->label('الدخل الكلي للأجهزة')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $deviceIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $playQuery = PlayHistory::whereIn('device_id', $deviceIds);
                                $invoiceQuery = Invoice::whereIn('device_id', $deviceIds)->where('status', 'posted');

                                if ($from) {
                                    $playQuery->whereDate('trx_date', '>=', $from);
                                    $invoiceQuery->where('issued_at', '>=', app(TenantClock::class)->localDateStartUtc($from));
                                }
                                if ($until) {
                                    $playQuery->whereDate('trx_date', '<=', $until);
                                    $invoiceQuery->where('issued_at', '<=', app(TenantClock::class)->localDateEndUtc($until));
                                }

                                $play = (float) $playQuery->sum('cost');
                                $orders = (float) $invoiceQuery->sum('order_amount');

                                return $play + $orders;
                            })
                    )
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
                    ->query(function ($query) {
                        // The columns states are queried on separate tables, so we just run the queries inside the column state.
                        return $query;
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
            ], layout: FiltersLayout::AboveContent);
    }
}
