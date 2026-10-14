<?php

namespace App\Filament\Pages;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\MarketHistory;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsProfitReport extends Page implements HasTable
{
    use HasPageShield;
    use InteractsWithTable;

    protected static ?string $title = 'أرباح ومبيعات المنتجات والخدمات';

    protected static ?string $navigationLabel = 'أرباح المنتجات والخدمات';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.products-profit-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CatalogItem::query()
                    ->where(fn (Builder $q) => $q->where('is_product', true)->orWhere('is_component', false))
                    ->with(['inventoryGroup'])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('المنتج أو الخدمة')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('inventoryGroup.name')
                    ->label('مجموعة الصنف')
                    ->placeholder('بدون مجموعة'),
                TextColumn::make('purchase_price')
                    ->label('سعر التكلفة')
                    ->money('EGP'),
                TextColumn::make('sale_price')
                    ->label('سعر البيع')
                    ->money('EGP'),
                TextColumn::make('qty_sold')
                    ->label('الكمية المباعة')
                    ->state(function (CatalogItem $record): int {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }

                        return (int) $query->sum('quantity');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي الكميات المباعة')
                            ->using(function ($query): int {
                                $itemIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $histQuery = MarketHistory::whereIn('catalog_item_id', $itemIds);
                                if ($from) {
                                    $histQuery->whereDate('transaction_date', '>=', $from);
                                }
                                if ($until) {
                                    $histQuery->whereDate('transaction_date', '<=', $until);
                                }

                                return (int) $histQuery->sum('quantity');
                            })
                    )
                    ->alignCenter(),
                TextColumn::make('total_revenue')
                    ->label('إجمالي الإيراد')
                    ->money('EGP')
                    ->state(function (CatalogItem $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }

                        return (float) $query->sum('total_amount');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي الإيرادات')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $itemIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $histQuery = MarketHistory::whereIn('catalog_item_id', $itemIds);
                                if ($from) {
                                    $histQuery->whereDate('transaction_date', '>=', $from);
                                }
                                if ($until) {
                                    $histQuery->whereDate('transaction_date', '<=', $until);
                                }

                                return (float) $histQuery->sum('total_amount');
                            })
                    )
                    ->sortable(),
                TextColumn::make('total_cost')
                    ->label('إجمالي التكلفة')
                    ->money('EGP')
                    ->state(function (CatalogItem $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }

                        return (float) $query->sum('total_cost');
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي تكلفة المبيعات')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $itemIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $histQuery = MarketHistory::whereIn('catalog_item_id', $itemIds);
                                if ($from) {
                                    $histQuery->whereDate('transaction_date', '>=', $from);
                                }
                                if ($until) {
                                    $histQuery->whereDate('transaction_date', '<=', $until);
                                }

                                return (float) $histQuery->sum('total_cost');
                            })
                    ),
                TextColumn::make('avg_cost')
                    ->label('متوسط سعر التكلفة')
                    ->money('EGP')
                    ->state(function (CatalogItem $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }

                        $qty = (float) $query->sum('quantity');
                        if ($qty <= 0) {
                            return (float) $record->purchase_price;
                        }

                        $cost = (float) $query->sum('total_cost');

                        return round($cost / $qty, 2);
                    })
                    ->alignCenter(),
                TextColumn::make('net_profit')
                    ->label('صافي الأرباح')
                    ->money('EGP')
                    ->state(function (CatalogItem $record): float {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }
                        $revenue = (float) $query->sum('total_amount');
                        $cost = (float) $query->sum('total_cost');

                        return $revenue - $cost;
                    })
                    ->weight('bold')
                    ->color(fn (float $state) => $state >= 0 ? 'success' : 'danger')
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي الأرباح الكلي')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $itemIds = $query->clone()->pluck('id');
                                $filterData = $this->tableFilters['issued_at'] ?? [];
                                $from = $filterData['from'] ?? null;
                                $until = $filterData['until'] ?? null;

                                $histQuery = MarketHistory::whereIn('catalog_item_id', $itemIds);
                                if ($from) {
                                    $histQuery->whereDate('transaction_date', '>=', $from);
                                }
                                if ($until) {
                                    $histQuery->whereDate('transaction_date', '<=', $until);
                                }
                                $revenue = (float) $histQuery->sum('total_amount');
                                $cost = (float) $histQuery->sum('total_cost');

                                return $revenue - $cost;
                            })
                    )
                    ->sortable(),
                TextColumn::make('profit_margin')
                    ->label('هامش الربح')
                    ->state(function (CatalogItem $record): string {
                        $filterData = $this->tableFilters['issued_at'] ?? [];
                        $from = $filterData['from'] ?? null;
                        $until = $filterData['until'] ?? null;

                        $query = MarketHistory::where('catalog_item_id', $record->id);
                        if ($from) {
                            $query->whereDate('transaction_date', '>=', $from);
                        }
                        if ($until) {
                            $query->whereDate('transaction_date', '<=', $until);
                        }
                        $revenue = (float) $query->sum('total_amount');
                        if ($revenue <= 0.0) {
                            return '-';
                        }
                        $cost = (float) $query->sum('total_cost');
                        $profit = $revenue - $cost;
                        $margin = ($profit / $revenue) * 100;

                        return number_format($margin, 1).'%';
                    })
                    ->alignCenter(),
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
                    ->query(function (Builder $query): Builder {
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
                SelectFilter::make('inventory_group_id')
                    ->label('المجموعة')
                    ->relationship('inventoryGroup', 'name'),
            ], layout: FiltersLayout::AboveContent);
    }
}
