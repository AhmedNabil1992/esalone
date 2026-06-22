<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\MarketHistory;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseStock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateDirectSaleAction
{
    /**
     * @param  array<int,array{catalog_item_id:int,quantity:int,unit_price:float,total_amount:float}>  $items
     * @return array<string,mixed>
     */
    public function execute(
        array $items,
        ?int $customerId = null,
        ?int $cashBoxId = null,
        float $discountAmount = 0,
        ?User $actor = null
    ): array {
        if (empty($items)) {
            throw new RuntimeException('يجب اختيار صنف واحد على الأقل.');
        }

        $actor ??= auth()->user();

        $activeShift = Shift::query()
            ->where('is_active', true)
            ->first();

        if (! $activeShift) {
            throw new RuntimeException('لا توجد فترة عمل نشطة.');
        }

        $defaultCashBox = $this->resolveCashBox($cashBoxId);

        return DB::transaction(function () use ($items, $customerId, $discountAmount, $actor, $activeShift, $defaultCashBox): array {
            // Calculate totals
            $subtotal = 0;
            $catalogItems = [];

            foreach ($items as $item) {
                $catalogItem = CatalogItem::query()->whereKey($item['catalog_item_id'])->first();
                if (! $catalogItem) {
                    throw new RuntimeException("الصنف {$item['catalog_item_id']} غير موجود.");
                }

                $catalogItems[$item['catalog_item_id']] = [
                    'item' => $catalogItem,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_amount' => $item['total_amount'],
                ];

                $subtotal += $item['total_amount'];
            }

            $subtotal = round($subtotal, 2);
            $discountAmount = round(min($discountAmount, $subtotal), 2);
            $afterDiscount = round($subtotal - $discountAmount, 2);

            $servicePercent = (float) SystemConfig::get('sys_service_percent', 0);
            $taxPercent = (float) SystemConfig::get('sys_tax_percent', 0);

            $serviceEnabled = (bool) SystemConfig::get('sys_service_on_market_invoices', '0');
            $taxEnabled = (bool) SystemConfig::get('sys_tax_on_market_invoices', '0');

            $serviceAmount = $serviceEnabled ? round(($afterDiscount * $servicePercent) / 100, 2) : 0.0;
            $taxBase = $afterDiscount + $serviceAmount;
            $taxAmount = $taxEnabled ? round(($taxBase * $taxPercent) / 100, 2) : 0.0;

            $finalAmount = round($afterDiscount + $serviceAmount + $taxAmount, 2);

            // Create invoice
            $invoiceNo = $this->generateInvoiceNo();

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->create([
                'invoice_no' => $invoiceNo,
                'invoice_type' => 'direct_sale',
                'customer_id' => $customerId,
                'shift_id' => $activeShift->id,
                'cash_box_id' => $defaultCashBox->id,
                'created_by_user_id' => $actor->id,
                'device_id' => null,
                'play_amount' => 0,
                'order_amount' => $subtotal,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_reason' => $discountAmount > 0 ? 'خصم مبيعات مباشرة' : null,
                'service_amount' => $serviceAmount,
                'tax_amount' => $taxAmount,
                'deposit_amount' => 0,
                'final_amount' => $finalAmount,
                'paid_amount' => $finalAmount,
                'remain_amount' => 0,
                'status' => 'posted',
                'issued_at' => now(),
            ]);

            // Record each item in market history and update stock
            $shiftNo = (int) ($activeShift->shift_no ?? 0);
            $now = Carbon::now();
            $defaultWarehouse = Warehouse::query()
                ->where('is_active', true)
                ->first();

            foreach ($catalogItems as $catalogItem) {
                MarketHistory::query()->create([
                    'customer_id' => $customerId,
                    'catalog_item_id' => $catalogItem['item']->id,
                    'subject_name' => $catalogItem['item']->name,
                    'unit_price' => $catalogItem['unit_price'],
                    'total_amount' => $catalogItem['total_amount'],
                    'quantity' => $catalogItem['quantity'],
                    'transaction_date' => $now->toDateString(),
                    'transaction_time' => $now->toTimeString(),
                    'created_by' => $actor->id,
                    'shift_no' => $shiftNo,
                    'invoice_no' => $invoiceNo,
                ]);

                // Record stock movement and update warehouse stock
                if ($defaultWarehouse) {
                    $warehouseStock = WarehouseStock::query()
                        ->where('warehouse_id', $defaultWarehouse->id)
                        ->where('catalog_item_id', $catalogItem['item']->id)
                        ->first();

                    if ($warehouseStock) {
                        $warehouseStock->decrement('quantity', $catalogItem['quantity']);
                    }

                    StockMovement::query()->create([
                        'warehouse_id' => $defaultWarehouse->id,
                        'catalog_item_id' => $catalogItem['item']->id,
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoiceNo,
                        'invoice_type' => 'direct_sale',
                        'created_by_user_id' => $actor->id,
                        'movement_type' => 'direct_sale',
                        'quantity' => -$catalogItem['quantity'],
                        'unit_cost' => (float) $catalogItem['item']->purchase_price,
                        'total_cost' => round(-$catalogItem['quantity'] * (float) $catalogItem['item']->purchase_price, 2),
                        'notes' => 'بيع مباشر - '.$invoiceNo,
                        'occurred_at' => $now,
                    ]);
                }
            }

            // Update cash box balance and record ledger entry
            $cashBoxToUpdate = CashBox::query()->whereKey($defaultCashBox->id)->first();
            if ($cashBoxToUpdate) {
                $cashBoxToUpdate->update([
                    'current_balance' => $cashBoxToUpdate->current_balance + $finalAmount,
                ]);

                // Record cash box ledger entry
                CashBoxLedgerEntry::query()->create([
                    'shift_id' => $activeShift->id,
                    'cash_box_id' => $defaultCashBox->id,
                    'created_by_user_id' => $actor->id,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                    'direction' => 'in',
                    'operation' => 'direct_sale',
                    'amount' => $finalAmount,
                    'description' => 'بيع مباشر - '.$invoiceNo,
                    'occurred_at' => now(),
                ]);
            }

            // Update customer balance if applicable
            if ($customerId) {
                $customer = Customer::query()->whereKey($customerId)->first();
                if ($customer) {
                    $customer->decrement('balance', $finalAmount);
                }
            }

            return [
                'invoice' => $invoice,
                'invoice_no' => $invoiceNo,
                'total_amount' => $finalAmount,
                'items_count' => count($catalogItems),
            ];
        });
    }

    private function resolveCashBox(?int $cashBoxId): CashBox
    {
        if ($cashBoxId) {
            $selectedCashBox = CashBox::query()
                ->whereKey($cashBoxId)
                ->where('is_active', true)
                ->first();

            if ($selectedCashBox) {
                return $selectedCashBox;
            }

            throw new RuntimeException('خزنة الدفع المختارة غير متاحة.');
        }

        $configuredCashBoxId = (int) SystemConfig::get('sys-default-cashbox', 0);
        if ($configuredCashBoxId > 0) {
            $configuredCashBox = CashBox::query()
                ->whereKey($configuredCashBoxId)
                ->where('is_active', true)
                ->first();

            if ($configuredCashBox) {
                return $configuredCashBox;
            }
        }

        $defaultCashBox = CashBox::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->first();

        if (! $defaultCashBox) {
            throw new RuntimeException('لا توجد خزنة نشطة.');
        }

        return $defaultCashBox;
    }

    private function generateInvoiceNo(): string
    {
        $today = Carbon::now();
        $prefix = $today->format('y-m-d');
        $dailyCount = Invoice::query()
            ->whereDate('issued_at', $today->toDateString())
            ->count() + 1;

        return sprintf('S-%s-%d', $prefix, $dailyCount);
    }
}
