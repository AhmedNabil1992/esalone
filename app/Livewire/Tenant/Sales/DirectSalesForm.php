<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Sales;

use App\Actions\Tenant\CreateDirectSaleAction;
use App\Jobs\Tenant\SendInvoiceWhatsAppJob;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\SystemConfig as Setting;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DirectSalesForm extends Component
{
    public ?int $inventory_group_id = null;

    public string $item_search = '';

    public string $customer_search = '';

    #[Validate('required|integer|min:1')]
    public ?int $selected_cash_box_id = null;

    #[Validate('nullable|integer')]
    public ?int $selected_customer_id = null;

    #[Validate('required|numeric|min:0')]
    public float $discount_amount = 0;

    #[Validate('required|numeric|min:0')]
    public float $paid_amount = 0;

    public bool $deposit_remainder_to_customer_balance = false;

    public string $whatsapp_phone = '';

    /** @var array<int,array{catalog_item_id:int,quantity:int,unit_price:float,total_amount:float}> */
    public array $selectedItems = [];

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('Create:DirectSale'), 403);

        $this->inventory_group_id = $this->orderGroups->first()?->id;
        $this->selected_cash_box_id = $this->defaultCashBoxId();
        $this->paid_amount = 0;
    }

    /** @return Collection<int, InventoryGroup> */
    public function getOrderGroupsProperty(): Collection
    {
        return InventoryGroup::query()
            ->where('is_active', true)
            ->where('name', '!=', 'مكونات')
            ->orderBy('name', 'asc')
            ->get();
    }

    /** @return Collection<int, CatalogItem> */
    public function getOrderItemsProperty(): Collection
    {
        if (! $this->inventory_group_id) {
            return collect();
        }

        $query = CatalogItem::query()
            ->where('inventory_group_id', $this->inventory_group_id)
            ->where('is_active', true);

        $term = trim($this->item_search);
        if ($term !== '') {
            $query->where('name', 'like', "%{$term}%");
        }

        return $query
            ->orderBy('name', 'asc')
            ->get();
    }

    /** @return Collection<int, CashBox> */
    public function getCashBoxesProperty(): Collection
    {
        return CashBox::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get(['id', 'name']);
    }

    public function getCanApplyDiscountProperty(): bool
    {
        return Auth::user()?->can(CreateDirectSaleAction::DISCOUNT_PERMISSION) ?? false;
    }

    private function defaultCashBoxId(): ?int
    {
        $configuredCashBoxId = (int) Setting::get('sys-default-cashbox', 0);
        if ($configuredCashBoxId > 0) {
            $configuredCashBox = CashBox::query()
                ->whereKey($configuredCashBoxId)
                ->where('is_active', true)
                ->value('id');

            if ($configuredCashBox) {
                return (int) $configuredCashBox;
            }
        }

        return CashBox::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->value('id');
    }

    /** @return Collection<int, Customer> */
    public function getCustomersProperty(): Collection
    {
        $query = Customer::query()->where('is_active', true);

        $term = trim($this->customer_search);
        if ($term !== '') {
            $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderBy('name', 'asc')
            ->limit(40)
            ->get(['id', 'name', 'phone']);
    }

    /** @return Collection<int, array{name:string,quantity:int,unit_price:float,total_amount:float,key:string}> */
    public function getSelectedItemRowsProperty(): Collection
    {
        if ($this->selectedItems === []) {
            return collect();
        }

        $itemIds = collect($this->selectedItems)
            ->pluck('catalog_item_id')
            ->filter()
            ->values()
            ->all();

        $itemsById = CatalogItem::query()
            ->get(['id', 'name'])
            ->whereIn('id', $itemIds)
            ->keyBy('id');

        return collect($this->selectedItems)
            ->map(function (array $row, string $key) use ($itemsById): array {
                $itemName = (string) ($itemsById->get($row['catalog_item_id'])?->name ?? '—');

                return [
                    'key' => $key,
                    'name' => $itemName,
                    'quantity' => (int) $row['quantity'],
                    'unit_price' => (float) $row['unit_price'],
                    'total_amount' => (float) $row['total_amount'],
                ];
            })
            ->values();
    }

    public function selectOrderGroup(int $groupId): void
    {
        $this->inventory_group_id = $groupId;
    }

    public function addItem(int $itemId, int $quantity = 1): void
    {
        $item = CatalogItem::query()->whereKey($itemId)->first();

        if (! $item) {
            Notification::make()
                ->danger()
                ->title('خطأ')
                ->body('الصنف غير موجود.')
                ->send();

            return;
        }

        $unitPrice = $item->getEffectiveSalePrice();
        $totalAmount = round($unitPrice * $quantity, 2);

        $key = "item_{$itemId}";

        if (isset($this->selectedItems[$key])) {
            $newQuantity = max(1, (int) $this->selectedItems[$key]['quantity'] + $quantity);
            $this->selectedItems[$key]['quantity'] = $newQuantity;
            $this->selectedItems[$key]['total_amount'] = round($unitPrice * $newQuantity, 2);

            $this->syncPaidAmount();

            Notification::make()
                ->success()
                ->title('تمت الإضافة')
                ->body("تمت زيادة كمية {$item->name} إلى {$newQuantity}.")
                ->send();

            return;
        }

        $this->selectedItems[$key] = [
            'catalog_item_id' => $itemId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $totalAmount,
        ];

        $this->syncPaidAmount();

        Notification::make()
            ->success()
            ->title('تمت الإضافة')
            ->body("{$item->name} تمت إضافتها للقائمة.")
            ->send();
    }

    public function incrementItem(string $key): void
    {
        if (! isset($this->selectedItems[$key])) {
            return;
        }

        $this->updateQuantity($key, (int) $this->selectedItems[$key]['quantity'] + 1);
    }

    public function decrementItem(string $key): void
    {
        if (! isset($this->selectedItems[$key])) {
            return;
        }

        $currentQuantity = (int) $this->selectedItems[$key]['quantity'];
        if ($currentQuantity <= 1) {
            $this->removeItem($key);

            return;
        }

        $this->updateQuantity($key, $currentQuantity - 1);
    }

    public function removeItem(string $key): void
    {
        unset($this->selectedItems[$key]);
        $this->syncPaidAmount();
        Notification::make()
            ->success()
            ->title('تمت الإزالة')
            ->send();
    }

    public function clearCart(): void
    {
        $this->selectedItems = [];
        $this->discount_amount = 0;
        $this->paid_amount = 0;
        $this->selected_customer_id = null;
        $this->deposit_remainder_to_customer_balance = false;
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        if (isset($this->selectedItems[$key]) && $quantity > 0) {
            $item = $this->selectedItems[$key];
            $item['quantity'] = $quantity;
            $item['total_amount'] = round($item['unit_price'] * $quantity, 2);

            $this->selectedItems[$key] = $item;

            $this->syncPaidAmount();
        }
    }

    private function syncPaidAmount(): void
    {
        // تحديث المبلغ المدفوع ليساوي الإجمالي النهائي بعد الخصم
        $this->paid_amount = $this->getFinalAmount();

        // مزامنة حالة زر "إيداع الباقي في رصيد العميل" بناءً على التحديث الجديد
        $this->syncDepositRemainderToggle();
    }

    public function saveSale(): void
    {
        abort_unless(auth()->user()?->can('Create:DirectSale'), 403);

        $this->validate();

        if (empty($this->selectedItems)) {
            Notification::make()
                ->danger()
                ->title('خطأ')
                ->body('يجب اختيار صنف واحد على الأقل.')
                ->send();

            return;
        }

        if (! $this->selected_cash_box_id) {
            Notification::make()
                ->danger()
                ->title('خطأ')
                ->body('يجب تحديد خزينة الدفع قبل حفظ الفاتورة.')
                ->send();

            return;
        }

        $selectedCashBoxExists = CashBox::query()
            ->whereKey($this->selected_cash_box_id)
            ->where('is_active', true)
            ->exists();

        if (! $selectedCashBoxExists) {
            Notification::make()
                ->danger()
                ->title('خطأ')
                ->body('خزينة الدفع المختارة غير متاحة. اختر الخزينة الافتراضية أو خزينة نشطة.')
                ->send();

            return;
        }

        try {
            $items = array_values($this->selectedItems);
            $submittedPaidAmount = $this->resolveSubmittedPaidAmount();

            $result = app(CreateDirectSaleAction::class)->execute(
                items: $items,
                customerId: $this->selected_customer_id,
                cashBoxId: $this->selected_cash_box_id,
                discountAmount: $this->canApplyDiscount ? $this->discount_amount : 0,
                paidAmount: $submittedPaidAmount,
                depositRemainderToCustomerBalance: $this->deposit_remainder_to_customer_balance,
            );

            Notification::make()
                ->success()
                ->title('تمت المبيعات')
                ->body("تم إنشاء الفاتورة {$result['invoice_no']} بنجاح.")
                ->send();

            $printActive = in_array((string) Setting::get('print_active', '0'), ['1', 'true', 'on', 'yes'], true);
            $printDirectSale = in_array((string) Setting::get('print_direct_sale', '0'), ['1', 'true', 'on', 'yes'], true);

            if ($printActive && $printDirectSale) {
                $this->dispatch('open-print-window', url: route('tenant.invoices.print', ['invoice' => $result['invoice']->id, 'silent' => 1]));
            }

            // Send WhatsApp invoice if enabled via Queue
            SendInvoiceWhatsAppJob::dispatch(
                tenant()->getTenantKey(),
                (int) $result['invoice']->id,
                $this->whatsapp_phone ?: null
            );
            $this->whatsapp_phone = '';

            $this->clearCart();
            $this->item_search = '';
            $this->customer_search = '';
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('خطأ')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getTotalAmount(): float
    {
        return (float) collect($this->selectedItems)
            ->sum('total_amount');
    }

    public function getFinalAmount(): float
    {
        $discountAmount = $this->canApplyDiscount ? $this->discount_amount : 0;

        return max(0, $this->getTotalAmount() - $discountAmount);
    }

    public function getRemainingAmount(): float
    {
        return round(max(0, $this->paid_amount) - $this->getFinalAmount(), 2);
    }

    public function updatedDiscountAmount(): void
    {
        if (! $this->canApplyDiscount) {
            $this->discount_amount = 0;
        }

        if ($this->paid_amount <= 0) {
            $this->paid_amount = $this->getFinalAmount();
        }

        $this->syncDepositRemainderToggle();
    }

    public function updatedPaidAmount(): void
    {
        $this->syncDepositRemainderToggle();
    }

    public function updatedSelectedCustomerId(): void
    {
        $this->syncDepositRemainderToggle();
    }

    private function syncDepositRemainderToggle(): void
    {
        if (! $this->selected_customer_id || $this->getRemainingAmount() <= 0) {
            $this->deposit_remainder_to_customer_balance = false;
        }
    }

    private function resolveSubmittedPaidAmount(): ?float
    {
        $normalizedPaidAmount = round(max(0, (float) $this->paid_amount), 2);

        if (! $this->selected_customer_id && $normalizedPaidAmount <= 0) {
            return null;
        }

        return $normalizedPaidAmount;
    }

    public function render()
    {
        return view('livewire.tenant.sales.direct-sales-form');
    }
}
