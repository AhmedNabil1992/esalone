<?php

namespace App\Filament\Resources\Tenant\StockMovements\Pages;

use App\Filament\Resources\Tenant\StockMovements\StockMovementResource;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\WarehouseStock;
use Filament\Facades\Filament;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $sourceWarehouseId = (int) ($data['warehouse_id'] ?? 0);
        $destinationWarehouseId = (int) ($data['destination_warehouse_id'] ?? 0);
        $items = $data['stock_items'] ?? [];

        if ($sourceWarehouseId <= 0 || $destinationWarehouseId <= 0) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'يجب اختيار المخزن المصدر والمخزن الهدف.',
            ]);
        }

        if ($sourceWarehouseId === $destinationWarehouseId) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'المخزن الهدف يجب أن يكون مختلفًا عن المخزن المصدر.',
            ]);
        }

        if (! is_array($items) || $items === []) {
            throw ValidationException::withMessages([
                'stock_items' => 'يجب إضافة صنف واحد على الأقل للنقل.',
            ]);
        }

        $actor = Filament::auth()->user();

        return DB::transaction(function () use ($sourceWarehouseId, $destinationWarehouseId, $items, $actor): Model {
            $lastCreatedMovement = null;

            foreach ($items as $index => $line) {
                $itemId = (int) ($line['catalog_item_id'] ?? 0);
                $quantity = (float) ($line['quantity'] ?? 0);

                if ($itemId <= 0) {
                    throw ValidationException::withMessages([
                        "stock_items.{$index}.catalog_item_id" => 'يرجى اختيار الصنف.',
                    ]);
                }

                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        "stock_items.{$index}.quantity" => 'الكمية يجب أن تكون أكبر من صفر.',
                    ]);
                }

                $sourceStock = WarehouseStock::query()
                    ->where('warehouse_id', $sourceWarehouseId)
                    ->where('catalog_item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (! $sourceStock || (float) $sourceStock->quantity < $quantity) {
                    throw ValidationException::withMessages([
                        "stock_items.{$index}.quantity" => 'الكمية المطلوبة أكبر من المتاح في المخزن المصدر.',
                    ]);
                }

                $destinationStock = WarehouseStock::query()
                    ->where('warehouse_id', $destinationWarehouseId)
                    ->where('catalog_item_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                if (! $destinationStock) {
                    $destinationStock = WarehouseStock::query()->create([
                        'warehouse_id' => $destinationWarehouseId,
                        'catalog_item_id' => $itemId,
                        'quantity' => 0,
                    ]);
                }

                $sourceStock->quantity = round((float) $sourceStock->quantity - $quantity, 3);
                $sourceStock->save();

                $destinationStock->quantity = round((float) $destinationStock->quantity + $quantity, 3);
                $destinationStock->save();

                $lastCreatedMovement = StockMovement::query()->create([
                    'warehouse_id' => $sourceWarehouseId,
                    'destination_warehouse_id' => $destinationWarehouseId,
                    'catalog_item_id' => $itemId,
                    'created_by_user_id' => $actor?->id,
                    'movement_type' => 'transfer',
                    'quantity' => -$quantity,
                    'unit_cost' => null,
                    'total_cost' => null,
                    'occurred_at' => now(),
                ]);

                StockMovement::query()->create([
                    'warehouse_id' => $destinationWarehouseId,
                    'destination_warehouse_id' => $sourceWarehouseId,
                    'catalog_item_id' => $itemId,
                    'created_by_user_id' => $actor?->id,
                    'movement_type' => 'transfer',
                    'quantity' => $quantity,
                    'unit_cost' => null,
                    'total_cost' => null,
                    'occurred_at' => now(),
                ]);
            }

            if (! $lastCreatedMovement) {
                throw ValidationException::withMessages([
                    'stock_items' => 'تعذر إنشاء حركات النقل. حاول مرة أخرى.',
                ]);
            }

            return $lastCreatedMovement;
        });
    }
}
