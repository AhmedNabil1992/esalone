<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\FinancialTransaction;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\User;
use App\Models\Tenant\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ReversePurchaseAction
{
    public function execute(Purchase $purchase, ?User $actor = null): Purchase
    {
        if (! self::canReverse($purchase->status)) {
            throw ValidationException::withMessages([
                'status' => 'Only posted purchases can be reversed.',
            ]);
        }

        return DB::transaction(function () use ($purchase, $actor): Purchase {
            $purchase->loadMissing(['items', 'shift']);

            foreach ($purchase->items as $purchaseItem) {
                $quantity = (float) $purchaseItem->quantity;

                $stock = WarehouseStock::query()
                    ->where('warehouse_id', $purchase->warehouse_id)
                    ->where('catalog_item_id', $purchaseItem->catalog_item_id)
                    ->lockForUpdate()
                    ->first();

                if (! $stock || (float) $stock->quantity < $quantity) {
                    throw new InvalidArgumentException('Insufficient warehouse stock to reverse this purchase.');
                }

                $stock->quantity = PostPurchaseAction::roundCurrency((float) $stock->quantity - $quantity);
                $stock->save();

                StockMovement::query()->create([
                    'warehouse_id' => $purchase->warehouse_id,
                    'catalog_item_id' => $purchaseItem->catalog_item_id,
                    'purchase_id' => $purchase->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'cash_box_id' => $purchase->cash_box_id,
                    'created_by_user_id' => $actor?->id,
                    'movement_type' => 'purchase_reverse',
                    'quantity' => -$quantity,
                    'unit_cost' => $purchaseItem->unit_cost,
                    'total_cost' => -((float) $purchaseItem->total_cost),
                    'notes' => $purchase->notes,
                    'occurred_at' => now(),
                ]);
            }

            $totalAmount = (float) $purchase->total_amount;

            if ($totalAmount > 0) {
                $cashBox = CashBox::query()
                    ->whereKey($purchase->cash_box_id)
                    ->lockForUpdate()
                    ->first();

                if ($cashBox) {
                    $cashBox->current_balance = PostPurchaseAction::roundCurrency((float) $cashBox->current_balance + $totalAmount);
                    $cashBox->save();
                }

                FinancialTransaction::query()->create([
                    'cash_box_id' => $purchase->cash_box_id,
                    'created_by_user_id' => $actor?->id,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'direction' => 'in',
                    'category' => 'purchase_reverse',
                    'amount' => $totalAmount,
                    'affects_profit' => false,
                    'description' => $purchase->notes,
                    'occurred_at' => now(),
                ]);

                if ($purchase->shift_id) {
                    $shift = Shift::query()
                        ->whereKey($purchase->shift_id)
                        ->lockForUpdate()
                        ->first();

                    if ($shift) {
                        $nextAmount = (float) $shift->purchase_amount - $totalAmount;

                        $shift->purchase_amount = PostPurchaseAction::roundCurrency(max($nextAmount, 0));
                        $shift->save();
                    }
                }
            }

            $purchase->update([
                'status' => 'reversed',
            ]);

            return $purchase->fresh(['items.item', 'warehouse', 'cashBox', 'supplier', 'shift']);
        });
    }

    public static function canReverse(string $status): bool
    {
        return $status === 'posted';
    }
}
