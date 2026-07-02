<?php

namespace App\Filament\Pages;

use App\Models\Tenant\WarehouseStock;
use Filament\Forms\Components\Select;
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

class InventoryReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'تقرير جرد وحالة المخزون';

    protected static ?string $navigationLabel = 'تقرير جرد وحالة المخزون';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'التقارير';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.inventory-report';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WarehouseStock::query()
                    ->with(['item', 'warehouse'])
            )
            ->columns([
                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('warehouse.name')
                    ->label('المخزن')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية المتوفرة')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('min_quantity')
                    ->label('الحد الأدنى للأمان')
                    ->numeric(decimalPlaces: 2)
                    ->alignCenter(),
                TextColumn::make('status')
                    ->label('حالة المخزون')
                    ->badge()
                    ->state(function (WarehouseStock $record): string {
                        $qty = (float) $record->quantity;
                        $min = (float) $record->min_quantity;

                        if ($qty <= 0.0) {
                            return 'out_of_stock';
                        }
                        if ($qty <= $min) {
                            return 'low_stock';
                        }

                        return 'in_stock';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'danger',
                        'low_stock' => 'warning',
                        'in_stock' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'out_of_stock' => 'نافد تماماً',
                        'low_stock' => 'مخزون منخفض',
                        'in_stock' => 'متوفر',
                        default => $state,
                    })
                    ->alignCenter(),
                TextColumn::make('item.purchase_price')
                    ->label('سعر تكلفة الشراء')
                    ->money('EGP')
                    ->placeholder('-'),
                TextColumn::make('total_value')
                    ->label('إجمالي قيمة المخزون')
                    ->money('EGP')
                    ->state(function (WarehouseStock $record): float {
                        return (float) $record->quantity * (float) ($record->item->purchase_price ?? 0.0);
                    })
                    ->summarize(
                        Summarizer::make()
                            ->label('إجمالي قيمة البضاعة بالمخازن')
                            ->money('EGP')
                            ->using(function ($query): float {
                                $stockIds = $query->clone()->pluck('id');
                                $stocks = WarehouseStock::whereIn('id', $stockIds)->with('item')->get();
                                $total = 0.0;
                                foreach ($stocks as $stock) {
                                    $total += (float) $stock->quantity * (float) ($stock->item->purchase_price ?? 0.0);
                                }

                                return $total;
                            })
                    )
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name'),
                Filter::make('stock_status')
                    ->form([
                        Select::make('status')
                            ->label('حالة المخزون')
                            ->options([
                                'out_of_stock' => 'نافد تماماً',
                                'low_stock' => 'مخزون منخفض',
                                'in_stock' => 'متوفر (سليم)',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status'], function (Builder $query, $status) {
                            if ($status === 'out_of_stock') {
                                return $query->where('quantity', '<=', 0);
                            }
                            if ($status === 'low_stock') {
                                return $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity');
                            }
                            if ($status === 'in_stock') {
                                return $query->whereColumn('quantity', '>', 'min_quantity');
                            }

                            return $query;
                        });
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['status'] ?? null)) {
                            return null;
                        }

                        return 'حالة المخزون: '.match ($data['status']) {
                            'out_of_stock' => 'نافد تماماً',
                            'low_stock' => 'مخزون منخفض',
                            'in_stock' => 'متوفر',
                            default => $data['status'],
                        };
                    }),
            ], layout: FiltersLayout::AboveContent);
    }
}
