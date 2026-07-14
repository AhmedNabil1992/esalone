<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\PurchaseItem;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\User;
use App\Models\Tenant\WarehouseStock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PostPurchaseAction
{
    /**
     * @param  array{
     *   warehouse_id:int,
     *   cash_box_id:int,
     *   supplier_id?:int|null,
     *   shift_id?:int|null,
     *   reference?:string|null,
     *   notes?:string|null,
     *   status?:string|null,
     *   purchased_at?:string|null,
     *   items:array<int, array{catalog_item_id:int, quantity:numeric-string|int|float, unit_cost:numeric-string|int|float}>
     * }  $payload
     */
    public function execute(array $payload, ?User $actor = null): Purchase
    {
        if (($payload['items'] ?? []) === []) {
            throw new InvalidArgumentException('Purchase must include at least one item.');
        }

        $cashBox = CashBox::query()
            ->whereKey($payload['cash_box_id'] ?? null)
            ->where('is_active', true)
            ->first();

        if (! $cashBox) {
            throw new InvalidArgumentException('An active cash box is required for purchases.');
        }

        $shift = isset($payload['shift_id'])
            ? Shift::query()
                ->whereKey($payload['shift_id'])
                ->active()
                ->open()
                ->first()
            : Shift::currentOpenShift();

        if (! $shift) {
            throw new InvalidArgumentException('An active open shift is required for purchases.');
        }

        $payload['shift_id'] = $shift->id;

        return DB::transaction(function () use ($payload, $actor): Purchase {
            $purchase = Purchase::query()->create([
                'warehouse_id' => $payload['warehouse_id'],
                'cash_box_id' => $payload['cash_box_id'],
                'supplier_id' => $payload['supplier_id'] ?? null,
                'shift_id' => $payload['shift_id'],
                'reference' => $payload['reference'] ?? null,
                'status' => $payload['status'] ?? 'posted',
                'notes' => $payload['notes'] ?? null,
                'total_amount' => 0,
                'purchased_at' => $payload['purchased_at'] ?? now(),
            ]);

            $totalAmount = 0.0;

            foreach ($payload['items'] as $line) {
                $quantity = (float) $line['quantity'];
                $unitCost = (float) $line['unit_cost'];

                if ($quantity <= 0) {
                    throw new InvalidArgumentException('Item quantity must be greater than zero.');
                }

                if ($unitCost < 0) {
                    throw new InvalidArgumentException('Item cost cannot be negative.');
                }

                $catalogItem = CatalogItem::query()->findOrFail($line['catalog_item_id']);

                if (! $catalogItem->is_active) {
                    throw new InvalidArgumentException('Catalog item must be active to be purchased.');
                }

                if ($catalogItem->is_product) {
                    throw new InvalidArgumentException('Products cannot be purchased directly. Use component items only.');
                }

                $lineTotal = self::calculateLineTotal($quantity, $unitCost);

                $purchaseItem = PurchaseItem::query()->create([
                    'purchase_id' => $purchase->id,
                    'catalog_item_id' => $catalogItem->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                ]);

                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->where('catalog_item_id', $catalogItem->id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock) {
                    $stock = WarehouseStock::query()->create([
                        'warehouse_id' => $purchase->warehouse_id,
                        'catalog_item_id' => $catalogItem->id,
                        'quantity' => 0,
                        'amount' => 0,
                    ]);
                }

                $stock->quantity = round((float) $stock->quantity + $quantity, 3);
                $stock->amount = round((float) $stock->amount + ($quantity * $unitCost), 4);
                $stock->save();

                // Update the catalog item's purchase price to the latest purchase unit cost
                $catalogItem->purchase_price = $unitCost;
                $catalogItem->save();

                StockMovement::query()->create([
                    'warehouse_id' => $purchase->warehouse_id,
                    'catalog_item_id' => $catalogItem->id,
                    'purchase_id' => $purchase->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'cash_box_id' => $purchase->cash_box_id,
                    'created_by_user_id' => $actor?->id,
                    'movement_type' => 'purchase',
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'notes' => $purchase->notes,
                    'occurred_at' => $purchase->purchased_at,
                ]);

                $totalAmount += $lineTotal;
            }

            $purchase->update([
                'total_amount' => self::roundCurrency($totalAmount),
            ]);

            if ((float) $purchase->total_amount > 0) {
                $cashBox = CashBox::query()
                    ->whereKey($purchase->cash_box_id)
                    ->lockForUpdate()
                    ->first();

                if ($cashBox) {
                    $cashBox->current_balance = self::roundCurrency((float) $cashBox->current_balance - (float) $purchase->total_amount);
                    $cashBox->save();
                }

                CashBoxLedgerEntry::query()->create([
                    'shift_id' => $purchase->shift_id,
                    'cash_box_id' => $purchase->cash_box_id,
                    'created_by_user_id' => $actor?->id,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'direction' => 'out',
                    'operation' => 'purchase',
                    'amount' => (float) $purchase->total_amount,
                    'description' => $purchase->notes,
                    'occurred_at' => $purchase->purchased_at,
                ]);

                if ($purchase->shift_id) {
                    $shift = Shift::query()
                        ->whereKey($purchase->shift_id)
                        ->lockForUpdate()
                        ->first();

                    if ($shift) {
                        $shift->purchase_amount = self::roundCurrency((float) $shift->purchase_amount + (float) $purchase->total_amount);
                        $shift->save();
                    }
                }
            }

            return $purchase->load(['items.item', 'warehouse', 'cashBox', 'supplier', 'shift']);
        });
    }

    public static function calculateLineTotal(float $quantity, float $unitCost): float
    {
        return self::roundCurrency($quantity * $unitCost);
    }

    public static function roundCurrency(float $amount): float
    {
        return round($amount, 2);
    }
}
