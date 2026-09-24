<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\CatalogItem;
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
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\TaxHistory;
use App\Models\Tenant\User;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseStock;
use App\Support\Tenant\TenantClock;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CloseSessionInvoiceAction
{
    public const string DISCOUNT_PERMISSION = 'Discount:DeviceControl';

    public ?Carbon $customCloseTime = null;

    /**
     * @return array<string,mixed>
     */
    public function preview(int $deviceId, float $discountAmount = 0, float $paidAmount = 0): array
    {
        /** @var Devices|null $device */
        $device = Devices::query()
            ->with([
                'activeSession.details' => fn ($query) => $query->orderBy('id', 'asc'),
                'activeSession.details.device.type',
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
    public function execute(int $deviceId, User $actor, float $paidAmount, float $discountAmount = 0, ?string $discountReason = null, ?array $paymentAllocations = null, ?int $customerId = null, bool $depositRemainderToCustomerBalance = false): Invoice
    {
        return DB::transaction(function () use ($deviceId, $actor, $paidAmount, $discountAmount, $discountReason, $paymentAllocations, $customerId, $depositRemainderToCustomerBalance): Invoice {
            if ($discountAmount > 0 && ! $actor->can(self::DISCOUNT_PERMISSION)) {
                throw new AuthorizationException('غير مصرح لك بإضافة خصم.');
            }

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
            $tenderedPaidAmount = round((float) ($preview['paid_amount'] ?? $paidAmount), 2);
            $cashPaidAmount = $this->resolveAppliedPaidAmount((float) $preview['final_amount'], $tenderedPaidAmount);
            $settledPaidAmount = $cashPaidAmount;
            $prepaidAmount = round((float) ($preview['deposit_amount'] ?? 0), 2);
            $customerBalanceDelta = $this->resolveCustomerBalanceDelta(
                (float) $preview['final_amount'],
                $tenderedPaidAmount,
                $depositRemainderToCustomerBalance,
            );
            $storedRemainAmount = $this->resolveStoredRemainAmount(
                (float) $preview['final_amount'],
                $tenderedPaidAmount,
                $customerBalanceDelta,
            );

            if ($customerBalanceDelta < 0) {
                $settledPaidAmount = round($settledPaidAmount + abs($customerBalanceDelta), 2);
                $storedRemainAmount = 0.00;
            }

            $totalPaidAmount = round($settledPaidAmount + $prepaidAmount, 2);
            $totalCashPaidAmount = round($cashPaidAmount + $prepaidAmount, 2);

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

            $finalDiscountReason = trim((string) $discountReason);
            if (! empty($preview['auto_discount_reason'])) {
                $finalDiscountReason = $finalDiscountReason !== ''
                    ? $finalDiscountReason.' + '.$preview['auto_discount_reason']
                    : $preview['auto_discount_reason'];
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
            if (count($resolvedAllocations) === 0) {
                $targetCashBox = $this->resolveDefaultCashBox(true);
                $targetCashBoxId = $targetCashBox?->id;
            } elseif (count($resolvedAllocations) === 1) {
                $targetCashBox = $resolvedAllocations[0]['cash_box'];
                $targetCashBoxId = $targetCashBox?->id;
            } else {
                $targetCashBox = $this->resolveDefaultCashBox(true);
                $targetCashBoxId = null;
            }

            $invoiceNo = $this->generateInvoiceNo();
            $resolvedBranchId = $this->resolveBranchId($device, $session, $actor);

            /** @var Invoice $invoice */
            $invoice = Invoice::query()->create([
                'branch_id' => $resolvedBranchId,
                'invoice_no' => $invoiceNo,
                'invoice_type' => 'device_sale',
                'device_id' => $device->id,
                'customer_id' => $resolvedCustomerId,
                'shift_id' => $shiftId,
                'cash_box_id' => $targetCashBoxId,
                'created_by_user_id' => $actor->id,
                'play_amount' => $preview['play_amount'],
                'order_amount' => $preview['order_amount'],
                'subtotal' => $preview['subtotal'],
                'discount_amount' => $preview['discount_amount'],
                'discount_reason' => $preview['discount_amount'] > 0 ? $finalDiscountReason : null,
                'service_amount' => $preview['service_amount'],
                'tax_amount' => $preview['tax_amount'],
                'deposit_amount' => $preview['deposit_amount'],
                'final_amount' => $preview['final_amount'],
                'paid_amount' => $totalPaidAmount,
                'remain_amount' => $storedRemainAmount,
                'status' => 'posted',
                'issued_at' => now(),
            ]);

            if ($resolvedAllocations !== []) {
                $normalizedAllocations = $this->normalizeAllocationsToAppliedAmount($resolvedAllocations, $cashPaidAmount);
                $normalizedAllocations = $this->addPrepaidAmountToAllocations($normalizedAllocations, $prepaidAmount, $resolvedAllocations[0]['cash_box']);
            } else {
                $normalizedAllocations = $targetCashBox && $totalCashPaidAmount > 0
                    ? [[
                        'cash_box' => $targetCashBox,
                        'cash_box_id' => (int) $targetCashBox->id,
                        'amount' => $totalCashPaidAmount,
                    ]]
                    : [];
            }

            $paymentLedgerEntries = $this->applySplitCashBoxPayments($invoice, $shiftId, $actor, $normalizedAllocations);
            $this->recordInvoicePaymentRows($invoice, $actor, $normalizedAllocations);
            $coreLedgerEntries = ['play' => null, 'orders' => $paymentLedgerEntries[0] ?? null];
            $breakdownLedgerEntries = ['tax' => null, 'service' => null, 'discount' => null];

            // Customer balance settles the invoice without moving cash in a cash box.
            if ($customerBalanceDelta < 0) {
                $cbId = $targetCashBox?->id ?? $this->resolveDefaultCashBox(true)?->id;
                InvoicePayment::query()->create([
                    'invoice_id' => $invoice->id,
                    'cash_box_id' => $cbId,
                    'created_by_user_id' => $actor->id,
                    'payment_method' => 'customer_balance',
                    'amount' => abs($customerBalanceDelta),
                    'notes' => 'خصم من رصيد حساب العميل',
                ]);

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
                    'branch_id' => $invoice->branch_id ?? $resolvedBranchId,
                    'invoice_id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'amount' => $preview['discount_amount'],
                    'reason' => $finalDiscountReason,
                    'shift_id' => $shiftId,
                    'user_id' => $actor->id,
                    'ledger_entry_id' => $breakdownLedgerEntries['discount'] ?? null,
                    'occurred_at' => now(),
                ]);
            }

            $this->insertPlayHistoryRows($session, $invoice, $actor, $shiftId);
            $this->insertMarketHistoryRows($device, $session, $invoice, $actor, $shiftId, $coreLedgerEntries['orders'] ?? null);

            // $this->updateShiftTotals($shiftId, $preview);
            $this->applyCustomerSettlement(
                $resolvedCustomerId,
                $actor,
                $invoice,
                $tenderedPaidAmount,
                $depositRemainderToCustomerBalance
            );

            OrderSessionDetail::query()
                ->where('play_session_id', $session->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);

            $device->update(['status' => 'active']);

            app(DispatchDeviceControlCommandAction::class)->execute($device, $actor, 'OFF', 'Finish Time');

            $session->delete();

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
                    'device_type' => (string) ($detail->device?->type?->name ?? ''),
                    'device_name' => (string) ($detail->device?->name ?? ''),
                    'play_type' => (string) ($detail->play_type ?? ''),
                    'play_category' => (string) ($detail->play_category ?? ''),
                    'start_time' => $detail->start_time,
                    'end_time' => $detail->end_time,
                    'play_price' => round((float) ($detail->play_price ?? 0), 2),
                    'period' => (int) ($detail->period ?? 0),
                    'cost' => $cost,
                ];
            })
            ->values();

        $orderRows = $session->orderSessionDetails
            ->map(function (OrderSessionDetail $order): array {
                $lineTotal = round((float) ($order->total ?? 0), 2);

                return [
                    'id' => (int) $order->id,
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

        $customer = $session->customer_id ? Customer::with('level')->find($session->customer_id) : null;
        $autoDiscount = 0.0;
        $autoDiscountReason = null;
        if ($customer && $customer->level && $customer->level->is_active) {
            $discountPercent = (float) $customer->level->discount_percent;
            if ($discountPercent > 0) {
                if ($customer->level->applies_to === 'play_only') {
                    $autoDiscount = round(($playAmount * $discountPercent) / 100, 2);
                } else {
                    $autoDiscount = round(($subtotal * $discountPercent) / 100, 2);
                }
                $autoDiscountReason = 'خصم فئة العملاء: '.$customer->level->name.' ('.number_format($discountPercent, 0).'%)';
            }
        }

        $totalDiscount = $discountAmount + $autoDiscount;
        if ($totalDiscount > $subtotal) {
            $totalDiscount = $subtotal;
        }

        $roundActive = $this->toBool(SystemConfig::get('round_money_active', '0'));
        if ($roundActive) {
            $totalDiscount = round(max(0, $totalDiscount));
        } else {
            $totalDiscount = round(max(0, $totalDiscount), 2);
        }
        $afterDiscount = round($subtotal - $totalDiscount, 2);

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
            'discount_amount' => $totalDiscount,
            'auto_discount_amount' => $autoDiscount,
            'auto_discount_reason' => $autoDiscountReason,
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
        $clock = app(TenantClock::class);
        $today = $clock->nowLocal();
        $prefix = $today->format('y-m-d');
        $dailyCount = Invoice::query()
            ->whereBetween('issued_at', $clock->localDateRangeUtc($today))
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
    // private function updateShiftTotals(int $shiftId, array $preview): void
    // {
    //     $shift = Shift::query()->whereKey($shiftId)->lockForUpdate()->first();
    //     if (! $shift) {
    //         return;
    //     }

    //     $shift->playstation_amount = round((float) $shift->playstation_amount + (float) $preview['play_amount'], 2);
    //     $shift->sales_amount = round((float) $shift->sales_amount + (float) $preview['order_amount'], 2);
    //     $shift->tax_amount = round((float) $shift->tax_amount + (float) $preview['tax_amount'], 2);
    //     $shift->service_amount = round((float) $shift->service_amount + (float) $preview['service_amount'], 2);
    //     $shift->discount_amount = round((float) $shift->discount_amount + (float) $preview['discount_amount'], 2);
    //     $shift->save();
    // }

    private function applyCustomerSettlement(?int $customerId, User $actor, Invoice $invoice, float $paidAmount, bool $depositRemainderToCustomerBalance): void
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

        $finalAmount = (float) $invoice->final_amount;
        $invoiceNo = $invoice->invoice_no;

        $balanceBefore = round((float) $customer->balance, 2);
        $amountDelta = $this->resolveCustomerBalanceDelta($finalAmount, $paidAmount, $depositRemainderToCustomerBalance);
        $balanceAfter = round($balanceBefore + $amountDelta, 2);

        $customer->balance = $balanceAfter;
        $customer->points = round((float) ($customer->points ?? 0) + $this->calculateEarnedPoints($invoice), 2);
        $customer->save();

        if ($amountDelta !== 0.0) {
            CustomerCredit::query()->create([
                'branch_id' => $invoice->branch_id ?? ($invoice->device?->branch_id ?? 1),
                'customer_id' => $customer->id,
                'user_id' => $actor->id,
                'type' => $amountDelta > 0 ? 'deposit' : 'withdraw',
                'amount' => abs($amountDelta),
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => 'فاتورة رقم '.$invoiceNo,
            ]);

            if ($amountDelta > 0) {
                $cbId = $invoice->cash_box_id ?? $this->resolveDefaultCashBox(true)?->id;
                if ($cbId) {
                    CashBoxLedgerEntry::query()->create([
                        'shift_id' => $invoice->shift_id,
                        'cash_box_id' => $cbId,
                        'created_by_user_id' => $actor->id,
                        'reference_type' => Invoice::class,
                        'reference_id' => $invoice->id,
                        'direction' => 'in',
                        'operation' => 'customer_credit_deposit',
                        'amount' => abs($amountDelta),
                        'description' => 'إيداع باقي الفاتورة رقم '.$invoiceNo,
                        'occurred_at' => now(),
                    ]);
                }
            }
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

    private function resolveAppliedPaidAmount(float $finalAmount, float $enteredPaidAmount): float
    {
        return round(min(max(0, $enteredPaidAmount), max(0, $finalAmount)), 2);
    }

    private function resolveCustomerBalanceDelta(float $finalAmount, float $enteredPaidAmount, bool $depositRemainderToCustomerBalance): float
    {
        $difference = round($enteredPaidAmount - $finalAmount, 2);

        if ($difference < 0) {
            return $difference;
        }

        if ($difference > 0 && $depositRemainderToCustomerBalance) {
            return $difference;
        }

        return 0.0;
    }

    private function resolveStoredRemainAmount(float $finalAmount, float $enteredPaidAmount, float $customerBalanceDelta): float
    {
        $difference = round($enteredPaidAmount - $finalAmount, 2);

        if ($difference < 0) {
            return $difference;
        }

        if ($difference > 0 && $customerBalanceDelta > 0) {
            return 0.0;
        }

        return 0.0;
    }

    private function calculateEarnedPoints(Invoice $invoice): float
    {
        $ratio = (float) SystemConfig::get('sys_customer_points_ratio', 0);
        if ($ratio <= 0) {
            return 0.0;
        }

        $type = SystemConfig::get('sys_customer_points_type', 'all');

        $baseAmount = 0.0;
        if ($type === 'play') {
            $baseAmount = (float) ($invoice->play_amount ?? 0);
        } elseif ($type === 'sales') {
            $baseAmount = (float) ($invoice->order_amount ?? 0);
        } else {
            // For 'all', calculate based on total invoice value before subtracting deposit
            $baseAmount = (float) $invoice->final_amount + (float) ($invoice->deposit_amount ?? 0);
        }

        if ($baseAmount <= 0) {
            return 0.0;
        }

        return round(($baseAmount * $ratio), 2);
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
                'branch_id' => $invoice->branch_id ?? $session->branch_id ?? ($session->device?->branch_id ?? 1),
                'device_id' => (int) ($detail->device_id ?? 0) ?: null,
                'play_type' => (string) ($detail->play_type ?? ''),
                'hour_price' => $detail->play_price,
                'start_time' => $detail->start_time,
                'end_time' => $detail->end_time,
                'actual_end_time' => now(),
                'play_time' => $detail->period,
                'cost' => $detail->cost,
                'username' => $actor->name,
                'shift_id' => $shiftId,
                'invoice_no' => $invoice->invoice_no,
                'trx_date' => app(TenantClock::class)->nowLocal()->toDateString(),
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

        $defaultWarehouse = Warehouse::query()
            ->where('is_active', true)
            ->first();

        foreach ($groupedRows as $rows) {
            /** @var Collection<int,OrderSessionDetail> $rows */
            $first = $rows->first();

            if (! $first instanceof OrderSessionDetail) {
                continue;
            }

            $quantity = (int) $rows->sum(static fn (OrderSessionDetail $row): int => (int) ($row->quantity ?? 0));
            $price = round((float) ($first->price ?? 0), 2);
            $catalogItem = CatalogItem::find($first->catalog_item_id);

            if (! $catalogItem) {
                continue;
            }

            $qtySold = (float) $quantity;

            // Load product components (eager loaded relationship)
            $components = $catalogItem->components()->with('component')->get();

            $averageCost = 0.0;
            $totalCost = 0.0;

            if ($components->isNotEmpty()) {
                // This is a composite product (BOM). Calculate total cost from components and deduct them from stock.
                $totalProductCost = 0.0;
                foreach ($components as $componentRelation) {
                    $componentItem = $componentRelation->component;
                    if (! $componentItem) {
                        continue;
                    }

                    $componentQtyNeeded = (float) $componentRelation->quantity * $qtySold;

                    $compStock = null;
                    $compAverageCost = 0.0;
                    if ($defaultWarehouse) {
                        $compStock = WarehouseStock::query()
                            ->where('warehouse_id', $defaultWarehouse->id)
                            ->where('catalog_item_id', $componentRelation->component_id)
                            ->first();

                        if ($compStock && (float) $compStock->quantity > 0) {
                            $compAverageCost = round((float) $compStock->amount / (float) $compStock->quantity, 4);
                        } else {
                            $compAverageCost = (float) $componentItem->purchase_price;
                        }
                    } else {
                        $compAverageCost = (float) $componentItem->purchase_price;
                    }

                    $compTotalCost = round($componentQtyNeeded * $compAverageCost, 4);
                    $totalProductCost += $compTotalCost;

                    // Deduct component from stock
                    if ($defaultWarehouse && $compStock) {
                        $newQty = max(0.0, (float) $compStock->quantity - $componentQtyNeeded);
                        $newAmount = max(0.0, (float) $compStock->amount - $compTotalCost);

                        if ($newQty <= 0.0) {
                            $newAmount = 0.0;
                        }

                        $compStock->update([
                            'quantity' => $newQty,
                            'amount' => $newAmount,
                        ]);
                    }

                    // Create StockMovement for the component
                    if ($defaultWarehouse) {
                        StockMovement::query()->create([
                            'branch_id' => $defaultWarehouse->branch_id ?? $invoice->branch_id,
                            'warehouse_id' => $defaultWarehouse->id,
                            'catalog_item_id' => $componentRelation->component_id,
                            'invoice_id' => $invoice->id,
                            'invoice_no' => $invoice->invoice_no,
                            'invoice_type' => 'session_sale',
                            'created_by_user_id' => $actor->id ?? null,
                            'movement_type' => 'direct_sale',
                            'quantity' => -$componentQtyNeeded,
                            'unit_cost' => $compAverageCost,
                            'total_cost' => -$compTotalCost,
                            'notes' => 'خصم مكونات صنف: '.$catalogItem->name.' - '.$invoice->invoice_no,
                            'occurred_at' => $issuedAt,
                        ]);
                    }
                }

                $averageCost = round($totalProductCost / $qtySold, 4);
                $totalCost = $totalProductCost;
            } else {
                // Regular item. Deduct directly from stock.
                $warehouseStock = null;

                if ($defaultWarehouse) {
                    $warehouseStock = WarehouseStock::query()
                        ->where('warehouse_id', $defaultWarehouse->id)
                        ->where('catalog_item_id', $catalogItem->id)
                        ->first();

                    if ($warehouseStock && (float) $warehouseStock->quantity > 0) {
                        $averageCost = round((float) $warehouseStock->amount / (float) $warehouseStock->quantity, 4);
                    } else {
                        $averageCost = (float) $catalogItem->purchase_price;
                    }
                } else {
                    $averageCost = (float) $catalogItem->purchase_price;
                }

                $totalCost = round($qtySold * $averageCost, 4);

                // Deduct from stock
                if ($defaultWarehouse) {
                    if ($warehouseStock) {
                        $newQty = max(0.0, (float) $warehouseStock->quantity - $qtySold);
                        $newAmount = max(0.0, (float) $warehouseStock->amount - $totalCost);

                        if ($newQty <= 0.0) {
                            $newAmount = 0.0;
                        }

                        $warehouseStock->update([
                            'quantity' => $newQty,
                            'amount' => $newAmount,
                        ]);
                    }

                    StockMovement::query()->create([
                        'branch_id' => $defaultWarehouse->branch_id ?? $invoice->branch_id,
                        'warehouse_id' => $defaultWarehouse->id,
                        'catalog_item_id' => $catalogItem->id,
                        'invoice_id' => $invoice->id,
                        'invoice_no' => $invoice->invoice_no,
                        'invoice_type' => 'session_sale',
                        'created_by_user_id' => $actor->id ?? null,
                        'movement_type' => 'direct_sale', // reuse direct_sale movement type for reports
                        'quantity' => -$qtySold,
                        'unit_cost' => $averageCost,
                        'total_cost' => -$totalCost,
                        'notes' => 'بيع من جلسة - '.$invoice->invoice_no,
                        'occurred_at' => $issuedAt,
                    ]);
                }
            }

            // Create MarketHistory record for the sold item (using calculated cost)
            MarketHistory::query()->create([
                'branch_id' => $invoice->branch_id ?? $session->branch_id ?? ($device->branch_id ?? 1),
                'customer_id' => $session->customer_id,
                'device_id' => $device->id,
                'catalog_item_id' => $catalogItem->id,
                'subject_name' => $catalogItem->name,
                'unit_price' => $price,
                'total_amount' => round($quantity * $price, 2),
                'unit_cost' => $averageCost,
                'total_cost' => $totalCost,
                'transaction_date' => $trxDate,
                'transaction_time' => $trxTime,
                'created_by' => $actor->id,
                'shift_id' => $shiftId,
                'ledger_entry_id' => $orderLedgerEntryId,
                'quantity' => $quantity,
                'invoice_no' => $invoice->invoice_no,
            ]);
        }
    }

    private function resolveEffectiveCloseTime(Devices $device, PlaySessionDetail $latestDetail): Carbon
    {
        $now = $this->customCloseTime ?? Carbon::instance(now()->toMutable());
        $playCategory = trim((string) ($latestDetail->play_category ?? ''));
        $detailEnd = $latestDetail->end_time;

        if ((bool) $device->is_time && $playCategory === 'محدد' && $detailEnd) {
            if ($now->greaterThan($detailEnd)) {
                $lockMinutes = (int) SystemConfig::get('sys_lock_device_time', 0);
                if ($lockMinutes > 0 && $detailEnd->diffInMinutes($now) >= $lockMinutes) {
                    throw new RuntimeException('لقد تجاوزت وقت السماح لتقفيل الجهاز. يرجى إضافة وقت أولاً.');
                }

                return Carbon::instance($detailEnd->toMutable());
            }

            return $now;
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
     * @param  array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>  $allocations
     * @return array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>
     */
    private function normalizeAllocationsToAppliedAmount(array $allocations, float $appliedPaidAmount): array
    {
        if ($appliedPaidAmount <= 0) {
            return [];
        }

        $remainingAmount = round($appliedPaidAmount, 2);
        $normalizedAllocations = [];

        foreach ($allocations as $allocation) {
            if ($remainingAmount <= 0) {
                break;
            }

            $allocationAmount = round(min((float) $allocation['amount'], $remainingAmount), 2);
            if ($allocationAmount <= 0) {
                continue;
            }

            $normalizedAllocations[] = [
                'cash_box' => $allocation['cash_box'],
                'cash_box_id' => $allocation['cash_box_id'],
                'amount' => $allocationAmount,
            ];

            $remainingAmount = round($remainingAmount - $allocationAmount, 2);
        }

        return $normalizedAllocations;
    }

    /**
     * @param  array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>  $allocations
     * @return array<int,array{cash_box:CashBox,cash_box_id:int,amount:float}>
     */
    private function addPrepaidAmountToAllocations(array $allocations, float $prepaidAmount, CashBox $cashBox): array
    {
        if ($prepaidAmount <= 0) {
            return $allocations;
        }

        if ($allocations === []) {
            return [[
                'cash_box' => $cashBox,
                'cash_box_id' => (int) $cashBox->id,
                'amount' => round($prepaidAmount, 2),
            ]];
        }

        $allocations[0]['amount'] = round((float) $allocations[0]['amount'] + $prepaidAmount, 2);

        return $allocations;
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

        $firstStart = $session->getOriginalStartTime();
        if (! $firstStart || ! $latestDetail->start_time) {
            return [
                'minutes' => $roundedMinutes,
                'applied' => false,
                'limit_time' => $limitTime,
                'elapsed_from_start' => 0,
            ];
        }

        $elapsedFromSessionStart = (int) max(0, $firstStart->diffInMinutes($effectiveEnd));
        if ($elapsedFromSessionStart >= $limitTime) {
            return [
                'minutes' => $roundedMinutes,
                'applied' => false,
                'limit_time' => $limitTime,
                'elapsed_from_start' => $elapsedFromSessionStart,
            ];
        }

        $beforeMinutes = (int) $details->filter(static fn (mixed $d): bool => $d instanceof PlaySessionDetail && $d->id < $latestDetail->id)->sum('period');
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

    private function resolveBranchId(Devices $device, PlaySessions $session, ?User $actor = null): int
    {
        if ($device->branch_id && is_numeric($device->branch_id) && (int) $device->branch_id > 0) {
            return (int) $device->branch_id;
        }

        if ($session->branch_id && is_numeric($session->branch_id) && (int) $session->branch_id > 0) {
            return (int) $session->branch_id;
        }

        $activeBranchId = session('active_branch_id');
        if ($activeBranchId && $activeBranchId !== 'all' && is_numeric($activeBranchId) && (int) $activeBranchId > 0) {
            return (int) $activeBranchId;
        }

        if ($actor && method_exists($actor, 'assignedBranchIds')) {
            $assignedIds = $actor->assignedBranchIds();
            if (! empty($assignedIds)) {
                return (int) $assignedIds[0];
            }
        }

        return 1;
    }
}
