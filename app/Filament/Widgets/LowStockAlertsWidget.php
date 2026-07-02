<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\WarehouseStock;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlertsWidget extends BaseWidget
{
    protected static ?string $heading = 'تنبيهات المخزون المنخفض (حد الأمان)';

    protected static ?int $sort = 5;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WarehouseStock::query()
                    ->whereColumn('quantity', '<=', 'min_quantity')
                    ->with(['item', 'warehouse'])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('item.name')
                    ->label('اسم الصنف')
                    ->weight('bold'),
                TextColumn::make('warehouse.name')
                    ->label('المخزن'),
                TextColumn::make('quantity')
                    ->label('الكمية المتبقية')
                    ->numeric(decimalPlaces: 2)
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('min_quantity')
                    ->label('حد الأمان')
                    ->numeric(decimalPlaces: 2),
            ]);
    }
}
