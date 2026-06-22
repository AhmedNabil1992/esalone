<?php

namespace App\Filament\Resources\Tenant\StockMovements\Schemas;

use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseStock;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;

class StockMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('warehouse_id')
                    ->label('المخزن المصدر (من)')
                    ->options(function (): Collection {
                        return Warehouse::query()
                            ->where('is_active', true)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function (callable $set): void {
                        $set('destination_warehouse_id', null);
                        $set('stock_items', []);
                    })
                    ->live(),

                Select::make('destination_warehouse_id')
                    ->label('المخزن الهدف (إلى)')
                    ->options(function (callable $get): Collection {
                        $sourceWarehouseId = $get('warehouse_id');

                        if (! $sourceWarehouseId) {
                            return Collection::make();
                        }

                        return Warehouse::query()
                            ->where('is_active', true)
                            ->where('id', '!=', $sourceWarehouseId)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->disabled(fn (callable $get): bool => blank($get('warehouse_id')))
                    ->required(),

                Repeater::make('stock_items')
                    ->label('بيانات النقل')
                    ->schema([
                        Select::make('catalog_item_id')
                            ->label('الصنف')
                            ->options(function (callable $get): Collection {
                                $warehouseId = $get('../../warehouse_id');

                                if (! $warehouseId) {
                                    return Collection::make();
                                }

                                return WarehouseStock::query()
                                    ->where('warehouse_id', $warehouseId)
                                    ->where('quantity', '>', 0)
                                    ->with('item')
                                    ->get()
                                    ->pluck('item.name', 'catalog_item_id');
                            })
                            ->searchable()
                            ->required()
                            ->live(),

                        TextInput::make('quantity')
                            ->label('الكمية المراد نقلها')
                            ->numeric()
                            ->step(0.001)
                            ->required()
                            ->helperText(function (callable $get): ?string {
                                $warehouseId = $get('../../warehouse_id');
                                $itemId = $get('catalog_item_id');

                                if (! $warehouseId || ! $itemId) {
                                    return null;
                                }

                                $availableQuantity = WarehouseStock::query()
                                    ->where('warehouse_id', $warehouseId)
                                    ->where('catalog_item_id', $itemId)
                                    ->value('quantity');

                                return $availableQuantity ? "الكمية المتاحة: {$availableQuantity}" : 'الصنف غير موجود في المخزن';
                            }),
                    ])
                    ->addActionLabel('إضافة صنف')
                    // ->deleteActionLabel('حذف')
                    ->reorderable(false)
                    ->collapsible()
                    ->minItems(1),
            ]);
    }
}
