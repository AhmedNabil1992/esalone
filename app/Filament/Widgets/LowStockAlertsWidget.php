<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\WarehouseStock;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertsWidget extends BaseWidget
{
    use HasWidgetShield;

    protected static ?string $heading = 'تنبيهات المخزون المنخفض (حد الأمان)';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WarehouseStock::query()
                    ->whereHas('warehouse')
                    ->join('catalog_items', 'warehouse_stocks.catalog_item_id', '=', 'catalog_items.id')
                    ->whereColumn('warehouse_stocks.quantity', '<=', 'catalog_items.min_quantity')
                    ->select('warehouse_stocks.*')
                    ->with(['item', 'warehouse.branch'])
                    ->latest('warehouse_stocks.updated_at')
            )
            ->columns([
                TextColumn::make('warehouse.branch.name')
                    ->label('الفرع')
                    ->badge(),
                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->weight('bold'),
                TextColumn::make('warehouse.name')
                    ->label('المخزن'),
                TextColumn::make('quantity')
                    ->label('الكمية المتبقية')
                    ->formatStateUsing(fn ($state) => (float) $state)
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('item.min_quantity')
                    ->label('حد الأمان')
                    ->formatStateUsing(fn ($state) => (float) $state),
            ]);
    }
}
