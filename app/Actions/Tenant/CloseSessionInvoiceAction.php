<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerCredit;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DiscountHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\MarketHistory;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlayHistory;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\ServiceHistory;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\TaxHistory;
use App\Models\Tenant\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CloseSessionInvoiceAction
{
    /**
     * @return array<string,mixed>
     */
    public function preview(int $deviceId, float $discountAmount = 0, float $paidAmount = 0): array
    {
        /** @var Devices|null $device */
        $device = Devices::query()
            ->with([
                'activeSession.details' => fn ($query) => $query->orderBy('id', 'asc'),
                'activeSession.orderSessionDetails.catalogItem',
            ])
            ->whereKey($deviceId)
            ->where('is_active', true)
            ->first();

        if (! $device || $device->status !== 'busy') {
            throw new RuntimeException('الجهاز غير مشغول حالياً.');
        }

        $session = $device->activeSession;
        if (! $session) {
            throw new RuntimeException('لا توجد جلسة نشطة لهذا الجهاز.');
        }

        /** @var PlaySessionDetail|null $latestDetail */
        $latestDetail = $session->details->last();

        if ($latestDetail && $latestDetail->start_time) {
            $effectiveEnd = $this->resolveEffectiveCloseTime($device, $latestDetail);
            $elapsedRawMinutes = (float) max(0, $latestDetail->start_time->diffInMinutes($effectiveEnd));
            $roundedMinutes = $this->roundMinutesForClose($elapsedRawMinutes);
            $limitTimeMeta = $this->applyDeviceLimitTimeOnClose($device, $session, $latestDetail, $effectiveEnd, $roundedMinutes);
            $roundedMinutes = $limitTimeMeta['minutes'];
            $segmentCost = $this->roundMoneyForClose(($roundedMinutes * (float) $latestDetail->play_price) / 60);

            $latestDetail->end_time = $effectiveEnd;
            $latestDetail->period = $roundedMinutes;
            $latestDetail->cost = $segmentCost;
        }

        return $this->buildPreviewPayload($device, $session, $discountAmount, $paidAmount, $limitTimeMeta ?? null);
    }

    /**
     * @param  array<int,array<string,mixed>>|null  $paymentAllocations
     */
    public function execute(int $deviceId, User $actor, float $paidAmount, float $discountAmount = 0, ?string $discountReason = null, ?array $paymentAllocations = null, ?int $customerId = null): Invoice
    {
        return DB::transaction(function () use ($deviceId, $actor, $paidAmount, $discountAmount, $discountReason, $paymentAllocations, $customerId): Invoice {
            if ($paidAmount < 0) {
                throw new RuntimeException('المبلغ المدفوع لا يمكن أن يكون سالباً.');
            }

            if ($discountAmount < 0) {
                throw new RuntimeException('قيمة الخصم لا يمكن أن تكون سالبة.');
            }

            /** @var Devices $device */
            $device = Devices::query()
                ->whereKey($deviceId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($device->status !== 'busy') {
                throw new RuntimeException('الجهاز غير مشغول حالياً.');
            }

            /** @var PlaySessions|null $session */
            $session = PlaySessions::query()
                ->where('device_id', $device->id)
                ->where('status', 'active')
                ->with([
                    'details' => fn ($query) => $query->orderBy('id', 'asc'),
                    'orderSessionDetails.catalogItem',
                ])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $session) {
                throw new RuntimeException('لا توجد جلسة نشطة لهذا الجهاز.');
            }

            /** @var PlaySessionDetail|null $latestDetail */
            $latestDetail = $session->details->last();
            if (! $latestDetail || ! $latestDetail->start_time) {
                throw new RuntimeException('لا توجد تفاصيل وقت صالحة لهذه الجلسة.');
            }

            $effectiveEnd = $this->resolveEffectiveCloseTime($device, $latestDetail);

            $elapsedRawMinutes = (float) max(0, $latestDetail->start_time->diffInMinutes($effectiveEnd));
            $roundedMinutes = $this->roundMinutesForClose($elapsedRawMinutes);
            $limitTimeMeta = $this->applyDeviceLimitTimeOnClose($device, $session, $latestDetail, $effectiveEnd, $roundedMinutes);
            $roundedMinutes = $limitTimeMeta['minutes'];
            $segmentCost = $this->roundMoneyForClose(($roundedMinutes * (float) $latestDetail->play_price) / 60);

            $latestDetail->update([
                'end_time' => $effectiveEnd,
                'period' => $roundedMinutes,
                'cost' => $segmentCost,
                'status_img' => 'مغلق',
                'status' => 'مغلق',
            ]);

            $session->refresh();
            $session->load([
                'details' => fn ($query) => $query->orderBy('id', 'asc'),
                'orderSessionDetails.catalogItem',
            ]);

            $resolvedAllocations = $this->resolvePaymentAllocations($paymentAllocations, true);
            if ($resolvedAllocations !== []) {
                $paidAmount = round((float) collect($resolvedAllocations)->sum('amount'), 2);
            }

            $preview = $this->buildPreviewPayload($device, $session, $discountAmount, $paidAmount, $limitTimeMeta ?? null);

            $resolvedCustomerId = is_numeric($session->customer_id)
                ? (int) $session->customer_id
                : null;

            if ($customerId && $customerId > 0) {
                $selectedCustomerExists = Customer::query()
                    ->whereKey($customerId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->exists();

                if (! $selectedCustomerExists) {
                    throw new RuntimeException('العميل المختار غير متاح.');
                }

                $resolvedCustomerId = $customerId;
            }

            if ($discountAmount > 0 && trim((string) $discountReason) === '') {
                throw new RuntimeException('يرجى إدخال سبب الخصم.');
            }

            if (! $resolvedCustomerId && (float) $preview['remain_amount'] < 0) {
                throw new RuntimeException('المبلغ المدفوع أقل من المطلوب.');
            }

            $shiftId = Shift::currentOpenShiftId();
            if (! $shiftId) {
                throw new RuntimeException('لا توجد وردية مفتوحة. يرجى فتح وردية أولاً.');
            }

            $shiftNo = (int) (Shift::query()->whereKey($shiftId)->value('shift_no') ?? $shiftId);
            $targetCashBox = count($resolvedAllocations) === 1
                ? $resolvedAllocations[0]['cash_box']
                : $this->resolveDefaultCashBox(true);
            $targetCashBoxId = $targetCashBox?->id;
            if (count($resolvedAllocations) > 1) {
                $targetCashBoxId = null;
            }

            $invoiceNo = $this->generateInvoiceNo();

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->create([
                'invoice_no' => $invoiceNo,
                'play_session_id' => $session->id,
                'device_id' => $device->id,
                'customer_id' => $resolvedCustomerId,
                'shift_id' => $shiftId,
                'cash_box_id' => $targetCashBoxId,
                'created_by_user_id' => $actor->id,
                'play_amount' => $preview['play_amount'],
                'order_amount' => $preview['order_amount'],
                'subtotal' => $preview['subtotal'],
                'discount_amount' => $preview['discount_amount'],
                'discount_reason' => $discountAmount > 0 ? trim((string) $discountReason) : null,
                'service_amount' => $preview['service_amount'],
                'tax_amount' => $preview['tax_amount'],
                'deposit_amount' => $preview['deposit_amount'],
                'final_amount' => $preview['final_amount'],
                'paid_amount' => $preview['paid_amount'],
                'remain_amount' => $preview['remain_amount'],
                'status' => 'posted',
                'issued_at' => now(),
            ]);

            if ($resolvedAllocations !== []) {
                $paymentLedgerEntries = $this->applySplitCashBoxPayments($invoice, $shiftId, $actor, $resolvedAllocations);
                $this->recordInvoicePaymentRows($invoice, $actor, $resolvedAllocations);
                $coreLedgerEntries = ['play' => null, 'orders' => $paymentLedgerEntries[0] ?? null];
                $breakdownLedgerEntries = ['tax' => null, 'service' => null, 'discount' => null];
            } else {
                $cashBoxId = $this->applyCashReceiptToCashBox((float) $preview['paid_amount'], $targetCashBox);
                if ((float) $preview['paid_amount'] > 0 && $cashBoxId) {
                    $this->recordInvoicePaymentRows($invoice, $actor, [
                        [
                            'cash_box' => $targetCashBox,
                            'cash_box_id' => $cashBoxId,
                            'amount' => round((float) $preview['paid_amount'], 2),
                            'payment_method' => 'cash',
                        ],
                    ]);
                }
                $coreLedgerEntries = $this->recordInvoiceCoreLedgerEntries($invoice, $shiftId, $actor, $cashBoxId, $targetCashBox, $preview);
                $breakdownLedgerEntries = $this->recordInvoiceBreakdownLedgerEntries($invoice, $shiftId, $actor, $cashBoxId, $targetCashBox, $preview);
            }

            if ((float) $preview['tax_amount'] > 0) {
                TaxHistory::query()->create([
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $preview['tax_amount'],
                    'shift_id' => $shiftId,
                    'user_id' => $actor->id,
                    'ledger_entry_id' => $breakdownLedgerEntries['tax'] ?? null,
                    'occurred_at' => now(),
                ]);
            }

            if ((float) $preview['service_amount'] > 0) {
                ServiceHistory::query()->create([
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $preview['service_amount'],
                    'shift_id' => $shiftId,
                    'user_id' => $actor->id,
                    'ledger_entry_id' => $breakdownLedgerEntries['service'] ?? null,
                    'occurred_at' => now(),
                ]);
            }

            if ((float) $preview['discount_amount'] > 0) {
                DiscountHistory::query()->create([
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $preview['discount_amount'],
                    'reason' => trim((string) $discountReason),
                    'shift_id' => $shiftId,
                    'user_id' => $actor->id,
                    'ledger_entry_id' => $breakdownLedgerEntries['discount'] ?? null,
                    'occurred_at' => now(),
                ]);
            }

            $this->insertPlayHistoryRows($session, $invoice, $actor, $shiftNo);
            $this->insertMarketHistoryRows($device, $session, $invoice, $actor, $shiftNo, $coreLedgerEntries['orders'] ?? null);

            $this->updateShiftTotals($shiftId, $preview);
            $this->applyCustomerSettlement(
                $resolvedCustomerId,
                $actor,
                (float) $preview['final_amount'],
                (float) $preview['paid_amount'],
                $invoice->invoice_no,
            );

            OrderSessionDetail::query()
                ->where('play_session_id', $session->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);

            $session->update([
                'status' => 'completed',
                'customer_id' => $resolvedCustomerId,
            ]);
            $device->update(['status' => 'active']);

            app(QueueDeviceControlCommandAction::class)->execute($device, $actor, 'OFF', 'Finish Time');

            return $invoice;
        });
    }

    /**
     * @return array<string,mixed>
     */
    private function buildPreviewPayload(Devices $device, PlaySessions $session, float $discountAmount, float $paidAmount, ?array $limitTimeMeta = null): array
    {
        $targetCashBox = $this->resolveDefaultCashBox();

        $timeRows = $session->details
            ->map(function (PlaySessionDetail $detail): array {
                $cost = round((float) ($detail->cost ?? 0), 2);

                return [
                    'play_type' => (string) ($detail->play_type ?? ''),
                    'play_category' => (string) ($detail->play_category ?? ''),
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'period' => (int) ($detail->period ?? 0),
                    'cost' => $cost,
                ];
            })
            ->values();

        $orderRows = $session->orderSessionDetails
            ->map(function (OrderSessionDetail $order): array {
                $lineTotal = round((float) ($order->total ?? 0), 2);

                return [
                    'item_name' => (string) ($order->catalogItem?->name ?? '—'),
                    'quantity' => (int) ($order->quantity ?? 0),
                    'price' => round((float) ($order->price ?? 0), 2),
                    'total' => $lineTotal,
                    'status' => (string) ($order->status ?? ''),
                ];
            })
            ->values();

        $playAmount = round((float) $timeRows->sum('cost'), 2);
        $orderAmount = round((float) $orderRows->sum('total'), 2);
        $subtotal = round($playAmount + $orderAmount, 2);

        if ($discountAmount > $subtotal) {
            throw new RuntimeException('لا يمكن أن يتجاوز الخصم إجمالي الحساب.');
        }

        $discountAmount = round(max(0, $discountAmount), 2);
        $afterDiscount = round($subtotal - $discountAmount, 2);

        $servicePercent = (float) SystemConfig::get('sys_service_percent', 0);
        $taxPercent = (float) SystemConfig::get('sys_tax_percent', 0);

        $serviceEnabled = $this->toBool(SystemConfig::get('sys_service_on_play_invoices', '0'));
        $taxEnabled = $this->toBool(SystemConfig::get('sys_tax_on_play_invoices', '0'));

        $serviceAmount = $serviceEnabled ? round(($afterDiscount * $servicePercent) / 100, 2) : 0.0;
        $taxBase = $afterDiscount + $serviceAmount;
        $taxAmount = $taxEnabled ? round(($taxBase * $taxPercent) / 100, 2) : 0.0;

        $depositAmount = round((float) ($session->prepaid_amount ?? 0), 2);
        $finalAmount = round($afterDiscount + $serviceAmount + $taxAmount - $depositAmount, 2);
        $paidAmount = round(max(0, $paidAmount), 2);
        $remainAmount = round($paidAmount - $finalAmount, 2);

        return [
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
            ],
            'session' => [
                'id' => $session->id,
                'session_key' => $session->session_key,
                'customer_id' => $session->customer_id,
            ],
            'time_rows' => $timeRows,
            'order_rows' => $orderRows,
            'play_amount' => $playAmount,
            'order_amount' => $orderAmount,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'service_amount' => $serviceAmount,
            'tax_amount' => $taxAmount,
            'deposit_amount' => $depositAmount,
            'final_amount' => $finalAmount,
            'paid_amount' => $paidAmount,
            'remain_amount' => $remainAmount,
            'cash_box' => [
                'id' => $targetCashBox?->id,
                'name' => $targetCashBox?->name,
            ],
            'limit_time' => [
                'applied' => (bool) ($limitTimeMeta['applied'] ?? false),
                'configured_minutes' => (int) ($limitTimeMeta['limit_time'] ?? 0),
                'elapsed_from_start' => (int) ($limitTimeMeta['elapsed_from_start'] ?? 0),
                'charged_minutes' => (int) ($limitTimeMeta['minutes'] ?? 0),
            ],
        ];
    }

    private function generateInvoiceNo(): string
    {
        $today = Carbon::now();
        $prefix = $today->format('y-m-d');
        $dailyCount = Invoice::query()
            ->whereDate('issued_at', $today->toDateString())
            ->count() + 1;

        return sprintf('%s-%d', $prefix, $dailyCount);
    }

    private function roundMinutesForClose(float $minutes): int
    {
        if ($minutes <= 0) {
            return 0;
        }

        $active = $this->toBool(SystemConfig::get('round_minutes_active', '0'));
        if (! $active) {
            return (int) ceil($minutes);
        }

        $step = (int) SystemConfig::get('round_minutes_close', 1);
        if ($step <= 0) {
            return (int) ceil($minutes);
        }

        $roundUp = $this->toBool(SystemConfig::get('round_minutes_up', '0'));
        $blocks = $minutes / $step;
        $roundedBlocks = $roundUp ? ceil($blocks) : floor($blocks);
        $result = (int) ($roundedBlocks * $step);

        return $result > 0 ? $result : $step;
    }

    private function roundMoneyForClose(float $amount): float
    {
        if ($amount <= 0) {
            return 0.0;
        }

        $active = $this->toBool(SystemConfig::get('round_money_active', '0'));
        if (! $active) {
            return round($amount, 2);
        }

        $step = (float) SystemConfig::get('round_money_close', 0);
        if ($step <= 0) {
            return round($amount, 2);
        }

        $roundUp = $this->toBool(SystemConfig::get('round_money_up', '0'));
        $blocks = $amount / $step;
        $roundedBlocks = $roundUp ? ceil($blocks) : floor($blocks);

        return round(max(0, $roundedBlocks * $step), 2);
    }

    /**
     * @param  array<string,mixed>  $preview
     */
    private function updateShiftTotals(int $shiftId, array $preview): void
    {
        $shift = Shift::query()->whereKey($shiftId)->lockForUpdate()->first();
        if (! $shift) {
            return;
        }

        $shift->playstation_amount = round((float) $shift->playstation_amount + (float) $preview['play_amount'], 2);
        $shift->sales_amount = round((float) $shift->sales_amount + (float) $preview['order_amount'], 2);
        $shift->tax_amount = round((float) $shift->tax_amount + (float) $preview['tax_amount'], 2);
        $shift->service_amount = round((float) $shift->service_amount + (float) $preview['service_amount'], 2);
        $shift->discount_amount = round((float) $shift->discount_amount + (float) $preview['discount_amount'], 2);
        $shift->save();
    }

    private function applyCustomerSettlement(?int $customerId, User $actor, float $finalAmount, float $paidAmount, string $invoiceNo): void
    {
        if (! $customerId) {
            return;
        }

        $customer = Customer::query()
            ->whereKey($customerId)
            ->lockForUpdate()
            ->first();

        if (! $customer) {
            return;
        }

        $balanceBefore = round((float) $customer->balance, 2);
        $amountDelta = round($paidAmount - $finalAmount, 2);
        $balanceAfter = round($balanceBefore + $amountDelta, 2);

        $customer->balance = $balanceAfter;
        $customer->points = round((float) ($customer->points ?? 0) + max(0, $finalAmount), 2);
        $customer->save();

        if ($amountDelta !== 0.0) {
            CustomerCredit::query()->create([
                'customer_id' => $customer->id,
                'user_id' => $actor->id,
                'type' => $amountDelta > 0 ? 'deposit' : 'withdraw',
                'amount' => abs($amountDelta),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'فاتورة رقم '.$invoiceNo,
            ]);
        }
    }

    private function applyCashReceiptToCashBox(float $paidAmount, ?CashBox $cashBox = null): ?int
    {
        $targetCashBox = $cashBox ?? $this->resolveDefaultCashBox(true);
        if (! $targetCashBox) {
            return null;
        }

        if ($paidAmount <= 0) {
            return (int) $targetCashBox->id;
        }

        $targetCashBox->current_balance = round((float) $targetCashBox->current_balance + $paidAmount, 2);
        $targetCashBox->save();

        return (int) $targetCashBox->id;
    }

    /**
     * @param  array<string,mixed>  $preview
     * @return array{play:?int,orders:?int}
     */
    private function recordInvoiceCoreLedgerEntries(Invoice $invoice, int $shiftId, User $actor, ?int $cashBoxId, ?CashBox $cashBox, array $preview): array
    {
        if (! $cashBoxId) {
            return ['play' => null, 'orders' => null];
        }

        $result = ['play' => null, 'orders' => null];

        $entries = [
            'play' => [
                'amount' => (float) ($preview['play_amount'] ?? 0),
                'description' => 'إيراد لعب فاتورة رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
                'operation' => 'play_invoice_play',
            ],
            'orders' => [
                'amount' => (float) ($preview['order_amount'] ?? 0),
                'description' => 'إيراد طلبات فاتورة رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
                'operation' => 'play_invoice_orders',
            ],
        ];

        foreach ($entries as $key => $entry) {
            if ($entry['amount'] <= 0) {
                continue;
            }

            $created = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $cashBoxId,
                'created_by_user_id' => $actor->id,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'direction' => 'in',
                'operation' => $entry['operation'],
                'amount' => round((float) $entry['amount'], 2),
                'description' => $entry['description'],
                'occurred_at' => now(),
            ]);

            $result[$key] = (int) $created->id;
        }

        return $result;
    }

    /**
     * @param  array<string,mixed>  $preview
     * @return array{tax:?int,service:?int,discount:?int}
     */
    private function recordInvoiceBreakdownLedgerEntries(Invoice $invoice, int $shiftId, User $actor, ?int $cashBoxId, ?CashBox $cashBox, array $preview): array
    {
        if (! $cashBoxId) {
            return ['tax' => null, 'service' => null, 'discount' => null];
        }

        $entries = [
            'tax' => [
                'amount' => (float) ($preview['tax_amount'] ?? 0),
                'direction' => 'in',
                'operation' => 'play_invoice_tax',
                'description' => 'ضريبة فاتورة لعب رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
            ],
            'service' => [
                'amount' => (float) ($preview['service_amount'] ?? 0),
                'direction' => 'in',
                'operation' => 'play_invoice_service',
                'description' => 'خدمة فاتورة لعب رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
            ],
            'discount' => [
                'amount' => (float) ($preview['discount_amount'] ?? 0),
                'direction' => 'out',
                'operation' => 'play_invoice_discount',
                'description' => 'خصم فاتورة لعب رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
            ],
        ];

        $result = ['tax' => null, 'service' => null, 'discount' => null];

        foreach ($entries as $key => $entry) {
            if ($entry['amount'] <= 0) {
                continue;
            }

            $created = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $cashBoxId,
                'created_by_user_id' => $actor->id,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'direction' => $entry['direction'],
                'operation' => $entry['operation'],
                'amount' => round((float) $entry['amount'], 2),
                'description' => $entry['description'],
                'occurred_at' => now(),
            ]);

            $result[$key] = (int) $created->id;
        }

        return $result;
    }

    private function insertPlayHistoryRows(PlaySessions $session, Invoice $invoice, User $actor, int $shiftId): void
    {
        $details = $session->details instanceof Collection
            ? $session->details
            : collect();

        if ($details->isEmpty()) {
            return;
        }

        foreach ($details as $detail) {
            if (! $detail instanceof PlaySessionDetail) {
                continue;
            }

            PlayHistory::query()->create([
                'device_id' => (int) ($detail->device_id ?? 0) ?: null,
                'play_type' => (string) ($detail->play_type ?? ''),
                'hour_price' => $detail->play_price,
                'start_time' => $detail->start_time,
                'end_time' => $detail->end_time,
                'actual_end_time' => now(),
                'play_time' => $detail->period,
                'cost' => $detail->cost,
                'username' => $actor->name,
                'shift_no' => $shiftId,
                'invoice_no' => $invoice->invoice_no,
                'trx_date' => now()->toDateString(),
            ]);
        }
    }

    private function insertMarketHistoryRows(Devices $device, PlaySessions $session, Invoice $invoice, User $actor, int $shiftId, ?int $orderLedgerEntryId = null): void
    {
        $orderRows = $session->orderSessionDetails instanceof Collection
            ? $session->orderSessionDetails
            : collect();

        if ($orderRows->isEmpty()) {
            return;
        }

        $issuedAt = $invoice->issued_at ?? now();
        $trxDate = $issuedAt->toDateString();
        $trxTime = $issuedAt->format('H:i:s');

        $groupedRows = $orderRows
            ->filter(static fn (mixed $row): bool => $row instanceof OrderSessionDetail)
            ->groupBy(function (OrderSessionDetail $row): string {
                $itemId = (int) ($row->catalog_item_id ?? 0);
                $price = number_format((float) ($row->price ?? 0), 2, '.', '');

                return $itemId.'|'.$price;
            });

        foreach ($groupedRows as $rows) {
            /** @var Collection<int,OrderSessionDetail> $rows */
            $first = $rows->first();

            if (! $first instanceof OrderSessionDetail) {
                continue;
            }

            $quantity = (int) $rows->sum(static fn (OrderSessionDetail $row): int => (int) ($row->quantity ?? 0));
            $price = round((float) ($first->price ?? 0), 2);

            MarketHistory::query()->create([
                'customer_id' => $session->customer_id,
                'device_id' => $device->id,
                'catalog_item_id' => $first->catalog_item_id,
                'subject_name' => $device->name,
                'unit_price' => $price,
                'total_amount' => round($quantity * $price, 2),
                'transaction_date' => $trxDate,
                'transaction_time' => $trxTime,
                'created_by' => $actor->name,
                'shift_no' => $shiftId,
                'ledger_entry_id' => $orderLedgerEntryId,
                'quantity' => $quantity,
                'invoice_no' => $invoice->invoice_no,
            ]);
        }
    }

    private function resolveEffectiveCloseTime(Devices $device, PlaySessionDetail $latestDetail): Carbon
    {
        $now = Carbon::instance(now()->toMutable());
        $playCategory = trim((string) ($latestDetail->play_category ?? ''));
        $detailEnd = $latestDetail->end_time;

        if ((bool) $device->is_time && $playCategory === 'وقت محدد' && $detailEnd) {
            if ($now->greaterThan($detailEnd)) {
                $lockMinutes = (int) SystemConfig::get('sys_lock_device_time', 0);
                if ($lockMinutes > 0 && $detailEnd->diffInMinutes($now) >= $lockMinutes) {
                    throw new RuntimeException('لقد تجاوزت وقت السماح لتقفيل الجهاز. يرجى إضافة وقت أولاً.');
                }
            }

            return Carbon::instance($detailEnd->toMutable());
        }

        return $now;
    }

    private function toBool(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @param  array<int,array<string,mixed>>|null  $allocations
     * @return array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>
     */
    private function resolvePaymentAllocations(?array $allocations, bool $forUpdate): array
    {
        if (! is_array($allocations) || $allocations === []) {
            return [];
        }

        $normalized = collect($allocations)
            ->map(function (mixed $row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $amount = round((float) ($row['amount'] ?? 0), 2);
                $cashBoxId = (int) ($row['cash_box_id'] ?? 0);
                if ($amount <= 0) {
                    return null;
                }

                if ($cashBoxId <= 0) {
                    throw new RuntimeException('يرجى اختيار خزينة صحيحة لكل دفعة.');
                }

                return [
                    'cash_box_id' => $cashBoxId,
                    'amount' => $amount,
                ];
            })
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            return [];
        }

        $cashBoxIds = $normalized->pluck('cash_box_id')->unique()->values();
        $cashBoxesQuery = CashBox::query()
            ->where('is_active', true)
            ->whereKey($cashBoxIds->all());

        if ($forUpdate) {
            $cashBoxesQuery->lockForUpdate();
        }

        $cashBoxes = $cashBoxesQuery->get()->keyBy('id');

        if ($cashBoxes->count() !== $cashBoxIds->count()) {
            throw new RuntimeException('هناك خزينة غير مفعلة أو غير موجودة ضمن الدفعات.');
        }

        return $normalized
            ->map(function (array $row) use ($cashBoxes): array {
                /** @var CashBox $cashBox */
                $cashBox = $cashBoxes->get($row['cash_box_id']);

                return [
                    'cash_box' => $cashBox,
                    'cash_box_id' => (int) $cashBox->id,
                    'amount' => round((float) $row['amount'], 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>  $allocations
     * @return array<int,int>
     */
    private function applySplitCashBoxPayments(Invoice $invoice, int $shiftId, User $actor, array $allocations): array
    {
        $entryIds = [];

        foreach ($allocations as $allocation) {
            if ($allocation['amount'] <= 0) {
                continue;
            }

            $cashBox = $allocation['cash_box'];
            $cashBox->current_balance = round((float) $cashBox->current_balance + (float) $allocation['amount'], 2);
            $cashBox->save();

            $entry = CashBoxLedgerEntry::query()->create([
                'shift_id' => $shiftId,
                'cash_box_id' => $allocation['cash_box_id'],
                'created_by_user_id' => $actor->id,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
                'direction' => 'in',
                'operation' => 'play_invoice_payment',
                'amount' => round((float) $allocation['amount'], 2),
                'description' => 'تحصيل فاتورة رقم '.$invoice->invoice_no.$this->ledgerCashBoxSuffix($cashBox),
                'occurred_at' => now(),
            ]);

            $entryIds[] = (int) $entry->id;
        }

        return $entryIds;
    }

    /**
     * @param  array<int,array{cash_box:CashBox|null,cash_box_id:int,amount:float}>  $allocations
     */
    private function recordInvoicePaymentRows(Invoice $invoice, User $actor, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            if ((float) ($allocation['amount'] ?? 0) <= 0 || (int) ($allocation['cash_box_id'] ?? 0) <= 0) {
                continue;
            }

            InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'cash_box_id' => (int) $allocation['cash_box_id'],
                'created_by_user_id' => $actor->id,
                'payment_method' => 'cash',
                'amount' => round((float) $allocation['amount'], 2),
                'notes' => null,
            ]);
        }
    }

    /**
     * @return array{minutes:int,applied:bool,limit_time:int,elapsed_from_start:int}
     */
    private function applyDeviceLimitTimeOnClose(Devices $device, PlaySessions $session, PlaySessionDetail $latestDetail, Carbon $effectiveEnd, int $roundedMinutes): array
    {
        $limitTime = (int) ($device->limit_time ?? 0);
        if ($limitTime <= 0) {
            return [
                'minutes' => $roundedMinutes,
                'applied' => false,
                'limit_time' => 0,
                'elapsed_from_start' => 0,
            ];
        }

        $details = $session->details instanceof Collection
            ? $session->details
            : collect();

        $firstDetailWithStart = $details->first(static fn (mixed $detail): bool => $detail instanceof PlaySessionDetail && $detail->start_time !== null);
        if (! $firstDetailWithStart instanceof PlaySessionDetail || ! $firstDetailWithStart->start_time || ! $latestDetail->start_time) {
            return [
                'minutes' => $roundedMinutes,
                'applied' => false,
                'limit_time' => $limitTime,
                'elapsed_from_start' => 0,
            ];
        }

        $elapsedFromSessionStart = (int) max(0, $firstDetailWithStart->start_time->diffInMinutes($effectiveEnd));
        if ($elapsedFromSessionStart >= $limitTime) {
            return [
                'minutes' => $roundedMinutes,
                'applied' => false,
                'limit_time' => $limitTime,
                'elapsed_from_start' => $elapsedFromSessionStart,
            ];
        }

        $beforeMinutes = (int) max(0, $firstDetailWithStart->start_time->diffInMinutes($latestDetail->start_time));
        if ($details->count() <= 1) {
            return [
                'minutes' => $limitTime,
                'applied' => true,
                'limit_time' => $limitTime,
                'elapsed_from_start' => $elapsedFromSessionStart,
            ];
        }

        $remainingLimitMinutes = $limitTime - $beforeMinutes;
        if ($remainingLimitMinutes <= 0) {
            return [
                'minutes' => 0,
                'applied' => true,
                'limit_time' => $limitTime,
                'elapsed_from_start' => $elapsedFromSessionStart,
            ];
        }

        return [
            'minutes' => $this->roundMinutesForClose((float) $remainingLimitMinutes),
            'applied' => true,
            'limit_time' => $limitTime,
            'elapsed_from_start' => $elapsedFromSessionStart,
        ];
    }

    private function resolveDefaultCashBox(bool $forUpdate = false): ?CashBox
    {
        $cashBoxId = (int) SystemConfig::get('sys_default_cashbox', 0);
        if ($cashBoxId <= 0) {
            return null;
        }

        $query = CashBox::query()
            ->whereKey($cashBoxId)
            ->where('is_active', true);

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function ledgerCashBoxSuffix(?CashBox $cashBox): string
    {
        $cashBoxName = trim((string) ($cashBox?->name ?? ''));
        if ($cashBoxName === '') {
            return '';
        }

        return ' - خزينة '.$cashBoxName;
    }
}
