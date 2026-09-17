<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use App\Mail\ShiftClosedMail;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CashBoxLedgerEntry;
use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CloseShift extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ShiftResource::class;

    protected string $view = 'filament.resources.tenant.shifts.shifts.pages.close-shift';

    public array $stats = [];

    public ?float $actual_amount = null;

    public array $cashbox_actuals = [];

    public string $notes = '';

    public array $playInvoices = [];

    public array $directSales = [];

    public array $expenses = [];

    public array $purchases = [];

    public array $cashBoxSummaries = [];

    public string $dataFingerprint = '';

    public bool $hasNewTransactions = false;

    public ?string $lastUpdatedTime = null;

    public static function authorizeResourceAccess(): void
    {
        abort_unless(static::canCloseShift(), 403);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return static::canCloseShift();
    }

    public function getCanViewFullShiftDetailsProperty(): bool
    {
        return Auth::user()?->can('Update:Shift') ?? false;
    }

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        $branchId = (int) ($this->record->branch_id ?: (Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1));

        if (! $this->record->branch_id) {
            $this->record->update(['branch_id' => $branchId]);
        }

        if ($this->record->status === 'close') {
            $this->actual_amount = (float) $this->record->actual_amount;
            $this->notes = (string) $this->record->notes;
            $this->cashbox_actuals = $this->record->cashbox_actual_amounts ?? [];
        } else {
            $this->cashbox_actuals = [];
            foreach (CashBox::query()->where('branch_id', $branchId)->where('is_active', true)->pluck('id') as $id) {
                $this->cashbox_actuals[$id] = null;
            }
        }

        $this->calculateStats();
        $this->dataFingerprint = $this->getShiftDataFingerprint();
    }

    public function updatedCashboxActuals(): void
    {
        $this->actual_amount = 0.0;
        foreach ($this->cashbox_actuals as $val) {
            if ($val !== '' && $val !== null) {
                $this->actual_amount += (float) $val;
            }
        }
        $this->actual_amount = round($this->actual_amount, 2);
    }

    public function calculateStats(): void
    {
        $shiftId = $this->record->id;
        $branchId = (int) ($this->record->branch_id ?: (Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1));

        // 1. Fetch Invoices closed during this shift
        $invoicesQuery = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->where('status', 'posted');
        $invoices = $invoicesQuery->get();

        $playInvoicesColl = $invoices->where('invoice_type', 'device_sale');
        $directSalesColl = $invoices->where('invoice_type', 'direct_sale');

        // Playstation gross play
        $playstation_amount = (float) $playInvoicesColl->sum('play_amount');

        // Store sales gross (orders + direct sales)
        $sales_amount = (float) ($playInvoicesColl->sum('order_amount') + $directSalesColl->sum('subtotal'));

        // Taxes, services, discounts, debts
        $tax_amount = (float) $invoices->sum('tax_amount');
        $service_amount = (float) $invoices->sum('service_amount');
        $discount_amount = (float) $invoices->sum('discount_amount');
        $credit_amount = (float) $invoices->sum('remain_amount'); // debt left on invoices

        // Payments via customer balance
        $customer_credit_amount = (float) InvoicePayment::query()
            ->where('branch_id', $branchId)
            ->whereIn('invoice_id', $invoices->pluck('id'))
            ->where('payment_method', 'customer_balance')
            ->sum('amount');

        // 2. Ledger cash movements during this shift
        $ledgerEntries = CashBoxLedgerEntry::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->get();

        // Customer deposits & withdrawals
        $customer_add_amount = (float) $ledgerEntries->where('operation', 'customer_credit_deposit')->sum('amount');
        $customer_minus_amount = (float) $ledgerEntries->where('operation', 'customer_credit_withdraw')->sum('amount');

        // Expenses & Purchases
        $expenses_amount = (float) $ledgerEntries->where('operation', 'expense')->sum('amount');
        $purchase_amount = (float) ($ledgerEntries->where('operation', 'purchase')->sum('amount') - $ledgerEntries->where('operation', 'purchase_reverse')->sum('amount'));

        // Income history
        $income_history_amount = 0.00;

        // Start cash
        $start_amount = (float) $this->record->start_amount;

        // Expected Cash Formula
        $expected_cash = $start_amount
            + $playstation_amount
            + $sales_amount
            + $tax_amount
            + $service_amount
            + $customer_add_amount
            + $income_history_amount
            - $purchase_amount
            - $expenses_amount
            - $discount_amount
            - $customer_credit_amount
            - $customer_minus_amount
            - $credit_amount;

        $expected_cash = round($expected_cash, 2);

        $this->stats = [
            'start_amount' => $start_amount,
            'playstation_amount' => $playstation_amount,
            'sales_amount' => $sales_amount,
            'tax_amount' => $tax_amount,
            'service_amount' => $service_amount,
            'discount_amount' => $discount_amount,
            'credit_amount' => $credit_amount,
            'customer_credit_amount' => $customer_credit_amount,
            'customer_add_amount' => $customer_add_amount,
            'customer_minus_amount' => $customer_minus_amount,
            'expenses_amount' => $expenses_amount,
            'purchase_amount' => $purchase_amount,
            'income_history_amount' => $income_history_amount,
            'expected_cash' => $expected_cash,
        ];

        // 3. Expected cash per cashbox
        $cashBoxes = CashBox::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->get();
        $this->cashBoxSummaries = [];
        $isSingleBox = $cashBoxes->count() === 1;

        foreach ($cashBoxes as $box) {
            $boxLedger = $ledgerEntries->where('cash_box_id', $box->id);
            $inflow = (float) $boxLedger->where('direction', 'in')->sum('amount');
            $outflow = (float) $boxLedger->where('direction', 'out')->sum('amount');

            if ($isSingleBox) {
                $netChange = round($expected_cash - $start_amount, 2);
                $expectedBalance = $expected_cash;
            } else {
                $netChange = round($inflow - $outflow, 2);
                $expectedBalance = round((float) $box->current_balance, 2);
            }

            $this->cashBoxSummaries[] = [
                'id' => $box->id,
                'name' => $box->name,
                'net_change' => $netChange,
                'current_balance' => $expectedBalance,
            ];
        }

        foreach ($cashBoxes as $box) {
            if (! array_key_exists($box->id, $this->cashbox_actuals)) {
                $this->cashbox_actuals[$box->id] = null;
            }
        }

        // 4. Load details lists for accordions
        $this->playInvoices = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->where('invoice_type', 'device_sale')
            ->where('status', 'posted')
            ->with(['customer', 'device', 'payments'])
            ->get()
            ->toArray();

        $this->directSales = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->where('invoice_type', 'direct_sale')
            ->where('status', 'posted')
            ->with(['customer', 'payments'])
            ->get()
            ->toArray();

        $this->expenses = ExpenseHistory::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->with(['cashBox', 'expenseType'])
            ->get()
            ->toArray();

        $this->purchases = Purchase::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->with(['cashBox', 'supplier'])
            ->get()
            ->toArray();
    }

    public function getShiftDataFingerprint(): string
    {
        $shiftId = $this->record->id;
        $branchId = (int) ($this->record->branch_id ?: (Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1));

        $invoicesAgg = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(final_amount), 0) as final_sum, COALESCE(SUM(play_amount), 0) as play_sum, COALESCE(SUM(order_amount), 0) as order_sum, COALESCE(SUM(subtotal), 0) as subtotal_sum, COALESCE(SUM(remain_amount), 0) as remain_sum, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        $invoiceIds = Invoice::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->pluck('id');

        $paymentsAgg = InvoicePayment::query()
            ->where('branch_id', $branchId)
            ->whereIn('invoice_id', $invoiceIds)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        $ledgerAgg = CashBoxLedgerEntry::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        $expensesAgg = ExpenseHistory::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        $purchasesAgg = Purchase::query()
            ->where('branch_id', $branchId)
            ->where('shift_id', $shiftId)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_amount), 0) as total, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        $cashBoxesAgg = CashBox::query()
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(current_balance), 0) as balance_total, COALESCE(MAX(updated_at), "") as max_up')
            ->first();

        return md5((string) json_encode([
            'inv' => $invoicesAgg?->toArray(),
            'pay' => $paymentsAgg?->toArray(),
            'led' => $ledgerAgg?->toArray(),
            'exp' => $expensesAgg?->toArray(),
            'pur' => $purchasesAgg?->toArray(),
            'box' => $cashBoxesAgg?->toArray(),
        ]));
    }

    public function checkForUpdates(): void
    {
        if ($this->record->status !== 'open') {
            return;
        }

        $currentFingerprint = $this->getShiftDataFingerprint();

        if ($currentFingerprint !== $this->dataFingerprint) {
            $this->calculateStats();
            $this->dataFingerprint = $currentFingerprint;
            $this->hasNewTransactions = true;
            $this->lastUpdatedTime = now()->format('h:i:s A');
            $this->updatedCashboxActuals();

            Notification::make()
                ->title('تنبيه: تم تسجيل عمليات جديدة بالوردية!')
                ->body('تم رصد حركات جديدة وتحديث بيانات الوردية تلقائياً. يرجى مراجعة الحسابات قبل إتمام التقفيل.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    public function refreshStats(): void
    {
        $this->calculateStats();
        $this->dataFingerprint = $this->getShiftDataFingerprint();
        $this->hasNewTransactions = false;
        $this->lastUpdatedTime = now()->format('h:i:s A');
        $this->updatedCashboxActuals();

        Notification::make()
            ->title('تم تحديث بيانات الوردية بنجاح')
            ->success()
            ->send();
    }

    public function acknowledgeNewTransactions(): void
    {
        $this->hasNewTransactions = false;
    }

    public function close(): void
    {
        abort_unless(static::canCloseShift(), 403);

        if ($this->record->status !== 'open') {
            Notification::make()->title('هذا الشيفت مغلق بالفعل')->warning()->send();

            return;
        }

        // Check if any new operations occurred that were not reflected/reviewed on screen
        $currentFingerprint = $this->getShiftDataFingerprint();
        if ($currentFingerprint !== $this->dataFingerprint) {
            $this->calculateStats();
            $this->dataFingerprint = $currentFingerprint;
            $this->hasNewTransactions = true;
            $this->lastUpdatedTime = now()->format('h:i:s A');
            $this->updatedCashboxActuals();

            Notification::make()
                ->title('تنبيه: حدثت عمليات جديدة قبل التقفيل!')
                ->body('تم رصد حركات جديدة وتحديث الأرقام تلقائياً. يرجى مراجعة الحسابات الجديدة أولاً ثم الضغط على تأكيد التقفيل مرة أخرى.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        // Validate each cashbox actual amount
        foreach ($this->cashBoxSummaries as $box) {
            $val = $this->cashbox_actuals[$box['id']] ?? null;
            if ($val === null || $val === '') {
                Notification::make()->title("يرجى إدخال الرصيد الفعلي لخزينة {$box['name']}")->warning()->send();

                return;
            }
        }

        // Save calculated columns and close
        $this->record->update([
            'playstation_amount' => $this->stats['playstation_amount'],
            'sales_amount' => $this->stats['sales_amount'],
            'tax_amount' => $this->stats['tax_amount'],
            'service_amount' => $this->stats['service_amount'],
            'discount_amount' => $this->stats['discount_amount'],
            'credit_amount' => $this->stats['credit_amount'],
            'customer_credit_amount' => $this->stats['customer_credit_amount'],
            'customer_add_amount' => $this->stats['customer_add_amount'],
            'customer_minus_amount' => $this->stats['customer_minus_amount'],
            'expenses_amount' => $this->stats['expenses_amount'],
            'purchase_amount' => $this->stats['purchase_amount'],
            'income_history_amount' => $this->stats['income_history_amount'],
            'actual_amount' => $this->actual_amount,
            'expected_amount' => $this->stats['expected_cash'],
            'cashbox_actual_amounts' => $this->cashbox_actuals,
            'notes' => $this->notes,
            'status' => 'close',
            'is_active' => false,
            'shift_to' => now(),
            'closed_by_user_id' => Auth::id(),
        ]);

        $notifyActive = (bool) SystemConfig::get('notify_active', '0');
        $ownerEmail = SystemConfig::get('notify_email', '');
        if ($notifyActive && ! empty($ownerEmail)) {
            try {
                Mail::to($ownerEmail)->send(new ShiftClosedMail($this->record->fresh(['opener', 'closer'])));
            } catch (\Throwable $e) {
                Log::error('Failed to send shift close email: '.$e->getMessage());
            }
        }

        Notification::make()->title('تم تقفيل الشيفت وحفظ البيانات بنجاح')->success()->send();

        $this->redirect(ShiftResource::getUrl('index'));
    }

    public function getTitle(): string
    {
        return 'تقفيل الشيفت رقم '.$this->record->shift_no;
    }

    private static function canCloseShift(): bool
    {
        $user = Auth::user();

        return $user?->can('Create:Shift') === true || $user?->can('Update:Shift') === true;
    }
}
