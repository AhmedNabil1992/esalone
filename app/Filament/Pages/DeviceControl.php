<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Actions\Tenant\AddSessionTimeAction;
use App\Actions\Tenant\CloseSessionInvoiceAction;
use App\Actions\Tenant\DispatchDeviceControlCommandAction;
use App\Actions\Tenant\LimitSessionTimeAction;
use App\Actions\Tenant\SendDeviceControlCommandViaBridgeAction;
use App\Actions\Tenant\StartSessionAction;
use App\Actions\Tenant\SwitchDeviceAction;
use App\Actions\Tenant\SwitchPlayAction;
use App\Events\Tenant\DeviceControlUpdated;
use App\Jobs\Tenant\SendInvoiceWhatsAppJob;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DeviceControl extends Page
{
    use HasPageShield;

    protected static ?string $title = 'التحكم في الأجهزة';

    protected static ?string $navigationLabel = 'التحكم في الأجهزة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static string|\UnitEnum|null $navigationGroup = 'اللعب';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.device-control';

    public function getListeners(): array
    {
        $tenantKey = $this->getTenantBroadcastKey();

        return [
            "echo:public:tenant.{$tenantKey}.device-control,device-control.updated" => '$refresh',
        ];
    }

    public ?int $selectedTypeId = null;

    public bool $showStartSessionModal = false;

    public bool $showReservationAlertModal = false;

    public ?int $pendingStartDeviceId = null;

    public ?int $pendingStartPlayPriceId = null;

    public bool $showBusyActionsModal = false;

    public bool $showBusyActionDetailsModal = false;

    public ?int $selectedDeviceId = null;

    public ?int $selectedBusyDeviceId = null;

    public ?string $selectedBusyActionLabel = null;

    public $busyActionAmount = null;

    public $busyActionMinutes = null;

    public $busyActionLimitMinutes = null;

    public $busyActionPaidAmount = null;

    /** @var array<string,mixed>|null */
    public ?array $busyClosePreview = null;

    public bool $busyCloseDepositRemainderToCustomerBalance = false;

    /**
     * @var array<int,array{cash_box_id:int|null,amount:float|int|string|null}>
     */
    public array $busyActionPayments = [];

    public $busyActionDiscountAmount = null;

    public ?string $busyActionDiscountReason = null;

    public $busyActionCustomerId = null;

    public string $busyActionCustomerSearch = '';

    public $selectedSwitchPlayPriceId = null;

    public $selectedSwitchDeviceId = null;

    public ?int $selectedOrderGroupId = null;

    public ?int $selectedCatalogItemId = null;

    public $orderItemQuantity = 1;

    public $orderItemNotes = null;

    public $selectedCustomerId = null;

    public string $customerSearch = '';

    public $selectedDurationMinutes = null;

    public $customDurationMinutes = null;

    public bool $bridgeUnavailable = false;

    public ?string $lastCloseInvoiceError = null;

    public ?string $lastCloseInvoiceDeviceName = null;

    public string $busyActionWhatsappPhone = '';

    private ?int $cancelOrderAllowedMinutesCache = null;

    public function mount(): void
    {
        $first = DevicesType::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->first();

        $this->selectedTypeId = $first?->id;

        $this->verifyBridgeConnectivity();

        // dd([
        //     'Config Timezone' => config('app.timezone'),
        //     'Carbon Now' => now()->format('Y-m-d H:i:s A'),
        // ]);
    }

    private function verifyBridgeConnectivity(): void
    {
        $controlActive = in_array(
            (string) SystemConfig::get('sys_control_active', '0'),
            ['1', 'true', 'on', 'yes'],
            true
        );

        if (! $controlActive) {
            $this->bridgeUnavailable = false;

            return;
        }

        $bridgeOnline = app(SendDeviceControlCommandViaBridgeAction::class)->isBridgeOnline();

        if (! $bridgeOnline) {
            $this->bridgeUnavailable = true;
            Notification::make()
                ->title('نظام التحكم متوقف')
                ->body('نظام التحكم فعال ولا يوجد اتصال مباشر بأجهزة التحكم')
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        $this->bridgeUnavailable = false;
    }

    private function isBridgeRequiredForDevice(?Devices $device): bool
    {
        if (! $device) {
            return false;
        }

        $ipAddress = trim((string) ($device->ip_address ?? ''));

        return $ipAddress !== '' && $ipAddress !== '127.0.0.1';
    }

    public function selectType(int $typeId): void
    {
        $this->selectedTypeId = $typeId;
    }

    /** @return Collection<int, DevicesType> */
    public function getDeviceTypesProperty(): Collection
    {
        return DevicesType::query()
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->get();
    }

    /** @return Collection<int, Devices> */
    public function getDevicesProperty(): Collection
    {
        if (! $this->selectedTypeId) {
            return collect();
        }

        return Devices::query()
            ->where('devices_type_id', $this->selectedTypeId)
            ->where('is_active', true)
            ->with(['type', 'playPrices.playType', 'playTypes', 'activeSession.latestDetail', 'activeSession.details', 'activeSession.customer'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /** @return Collection<int, Customer> */
    public function getCustomersProperty(): Collection
    {
        return Customer::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /** @return Collection<int, Customer> */
    public function getFilteredCustomersProperty(): Collection
    {
        $query = Customer::query()->where('is_active', true);

        $term = trim($this->customerSearch);
        if ($term !== '') {
            $query->where(function ($subQuery) use ($term): void {
                $subQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();
    }

    public function openStartSessionModal(int $deviceId): void
    {
        $device = Devices::query()
            ->where('id', $deviceId)
            ->where('is_active', true)
            ->first();

        if (! $device) {
            Notification::make()->title('الجهاز غير متاح')->danger()->send();

            return;
        }

        if ($device->status === 'busy') {
            Notification::make()->title('الجهاز مشغول حالياً')->warning()->send();

            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($device)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن فتح وقت للجهاز بدون Bridge.')
                ->warning()
                ->send();

            return;
        }

        $this->selectedDeviceId = $device->id;
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
        $this->selectedDurationMinutes = null;
        $this->customDurationMinutes = null;
        $this->showStartSessionModal = true;
    }

    public function closeStartSessionModal(): void
    {
        $this->showStartSessionModal = false;
        $this->selectedDeviceId = null;
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
        $this->selectedDurationMinutes = null;
        $this->customDurationMinutes = null;
    }

    public function openBusyActionsModal(int $deviceId): void
    {
        $device = Devices::query()
            ->where('id', $deviceId)
            ->where('is_active', true)
            ->first();

        if (! $device || $device->status !== 'busy') {
            Notification::make()->title('الجهاز غير مشغول حالياً')->warning()->send();

            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($device)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن إجراء عمليات على هذا الجهاز بدون Bridge.')
                ->warning()
                ->send();

            return;
        }

        $this->selectedBusyDeviceId = $device->id;
        $this->selectedBusyActionLabel = null;
        $this->showBusyActionDetailsModal = false;
        $this->showBusyActionsModal = true;
    }

    public function openCloseInvoiceModal(int $deviceId): void
    {
        $this->openBusyActionsModal($deviceId);

        if ($this->selectedBusyDeviceId) {
            $this->openBusyActionDetails('تقفيل الحساب');
        }
    }

    public function closeBusyActionsModal(): void
    {
        $this->showBusyActionsModal = false;
        $this->selectedBusyDeviceId = null;
    }

    public function openBusyActionDetails(string $actionLabel): void
    {
        $this->lastCloseInvoiceError = null;
        $this->lastCloseInvoiceDeviceName = null;
        $this->selectedBusyActionLabel = $actionLabel;
        $this->busyActionAmount = null;
        $this->busyActionMinutes = null;
        $this->busyActionLimitMinutes = null;
        $this->busyActionPaidAmount = null;
        $this->busyClosePreview = null;
        $this->busyCloseDepositRemainderToCustomerBalance = false;
        $this->busyActionPayments = [];
        $this->busyActionDiscountAmount = null;
        $this->busyActionDiscountReason = null;
        $this->busyActionCustomerId = null;
        $this->busyActionCustomerSearch = '';
        $this->selectedSwitchPlayPriceId = null;
        $this->selectedSwitchDeviceId = null;
        $this->selectedCatalogItemId = null;
        $this->orderItemQuantity = 1;
        $this->orderItemNotes = null;

        if (in_array($actionLabel, ['تقفيل الحساب', 'بيانات الجهاز'], true)) {
            $busySessionCustomer = $this->selectedBusyDevice?->activeSession?->customer;
            $this->busyActionCustomerId = $busySessionCustomer?->id;
            $this->busyActionCustomerSearch = $busySessionCustomer?->name ?? '';
        }

        if ($actionLabel === 'إضافة طلبات') {
            $firstGroup = InventoryGroup::query()
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->first();

            $this->selectedOrderGroupId = $firstGroup?->id;
        }

        if ($actionLabel === 'تبديل اللعب') {
            $this->selectedSwitchPlayPriceId = $this->availableSwitchPlayPrices->first()?->id;
        }

        if ($actionLabel === 'تبديل أجهزة') {
            $this->selectedSwitchDeviceId = $this->availableSwitchDevices->first()?->id;
        }

        if ($actionLabel === 'تقفيل الحساب') {
            try {
                $busySessionCustomer = $this->selectedBusyDevice?->activeSession?->customer;
                $this->busyActionDiscountAmount = 0;
                $this->busyActionDiscountReason = null;
                $this->busyActionCustomerId = $busySessionCustomer?->id;
                $this->busyActionCustomerSearch = $busySessionCustomer?->name ?? '';
                $basePreview = $this->buildBusyClosePreview(0.0);
                $this->busyClosePreview = $basePreview;

                $this->busyActionPaidAmount = (float) ($basePreview['final_amount'] ?? 0);
                $defaultCashBoxId = data_get($basePreview, 'cash_box.id');
                $this->busyActionPayments = [
                    [
                        'cash_box_id' => is_numeric($defaultCashBoxId) ? (int) $defaultCashBoxId : null,
                        'amount' => (float) ($this->busyActionPaidAmount ?? 0),
                    ],
                ];
                $this->busyClosePreview = $this->buildBusyClosePreview((float) ($this->busyActionPaidAmount ?? 0));
            } catch (RuntimeException $e) {
                Notification::make()
                    ->title('تنبيه وقت السماح')
                    ->body($e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();

                $this->showBusyActionsModal = false;
                $this->showBusyActionDetailsModal = false;
                $this->selectedBusyDeviceId = null;
                $this->selectedBusyActionLabel = null;

                return;
            }
        }

        $this->showBusyActionsModal = false;
        $this->showBusyActionDetailsModal = true;
    }

    public function printUnprintedOrders(): void
    {
        if (! $this->selectedBusyDeviceId) {
            return;
        }

        /** @var PlaySessions|null $session */
        $session = PlaySessions::query()
            ->where('device_id', $this->selectedBusyDeviceId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $session) {
            return;
        }

        $unprintedDetails = OrderSessionDetail::query()
            ->where('play_session_id', $session->id)
            ->where('printed', false)
            ->with('catalogItem.inventoryGroup')
            ->get();

        if ($unprintedDetails->isEmpty()) {
            return;
        }

        $printActive = in_array((string) SystemConfig::get('print_active', '0'), ['1', 'true', 'on', 'yes'], true);
        $printOrderPrepare = in_array((string) SystemConfig::get('print_order_prepare', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($printActive && $printOrderPrepare) {
            $defaultOrderPrinter = (string) SystemConfig::get('sys_order_printer', '');
            $multiPrinters = in_array((string) SystemConfig::get('print_multi_printers', '0'), ['1', 'true', 'on', 'yes'], true);

            if ($multiPrinters) {
                $grouped = $unprintedDetails->groupBy(function (OrderSessionDetail $detail) use ($defaultOrderPrinter) {
                    $printerName = trim((string) ($detail->catalogItem?->inventoryGroup?->printer_name ?? ''));

                    return $printerName !== '' ? $printerName : $defaultOrderPrinter;
                });

                foreach ($grouped as $printerName => $details) {
                    $idsCsv = $details->pluck('id')->implode(',');
                    $this->dispatch('open-print-window', url: route('tenant.order-prepares.print', [
                        'ids' => $idsCsv,
                        'printer' => $printerName,
                        'silent' => 1,
                    ]));
                }
            } else {
                $idsCsv = $unprintedDetails->pluck('id')->implode(',');
                $this->dispatch('open-print-window', url: route('tenant.order-prepares.print', [
                    'ids' => $idsCsv,
                    'printer' => $defaultOrderPrinter,
                    'silent' => 1,
                ]));
            }
        }

        OrderSessionDetail::whereIn('id', $unprintedDetails->pluck('id'))->update(['printed' => true]);
    }

    public function closeBusyActionDetailsModal(): void
    {
        if ($this->selectedBusyActionLabel === 'إضافة طلبات') {
            $this->printUnprintedOrders();
        }

        $this->showBusyActionDetailsModal = false;
        $this->selectedBusyActionLabel = null;
        $this->busyActionAmount = null;
        $this->busyActionMinutes = null;
        $this->busyActionLimitMinutes = null;
        $this->busyActionPaidAmount = null;
        $this->busyClosePreview = null;
        $this->busyCloseDepositRemainderToCustomerBalance = false;
        $this->busyActionPayments = [];
        $this->busyActionDiscountAmount = null;
        $this->busyActionDiscountReason = null;
        $this->busyActionCustomerId = null;
        $this->busyActionCustomerSearch = '';
        $this->selectedSwitchPlayPriceId = null;
        $this->selectedSwitchDeviceId = null;
        $this->selectedOrderGroupId = null;
        $this->selectedCatalogItemId = null;
        $this->orderItemQuantity = 1;
        $this->orderItemNotes = null;
        $this->busyActionWhatsappPhone = '';
        $this->lastCloseInvoiceError = null;
        $this->lastCloseInvoiceDeviceName = null;
    }

    public function selectSwitchPlayPrice(int $playPriceId): void
    {
        $exists = $this->availableSwitchPlayPrices
            ->contains(fn ($price): bool => (int) $price->id === $playPriceId);

        if (! $exists) {
            return;
        }

        $this->selectedSwitchPlayPriceId = $playPriceId;
    }

    public function selectSwitchDevice(int $deviceId): void
    {
        $exists = $this->availableSwitchDevices
            ->contains(fn (Devices $device): bool => (int) $device->id === $deviceId);

        if (! $exists) {
            return;
        }

        $this->selectedSwitchDeviceId = $deviceId;
    }

    public function setQuickDuration(int $minutes): void
    {
        $this->selectedDurationMinutes = $minutes;
        $this->customDurationMinutes = $minutes;
    }

    public function clearDuration(): void
    {
        $this->selectedDurationMinutes = null;
        $this->customDurationMinutes = null;
    }

    public function getSelectedDeviceProperty(): ?Devices
    {
        if (! $this->selectedDeviceId) {
            return null;
        }

        return Devices::query()
            ->with(['playPrices.playType'])
            ->find($this->selectedDeviceId);
    }

    public function getSelectedBusyDeviceProperty(): ?Devices
    {
        if (! $this->selectedBusyDeviceId) {
            return null;
        }

        return Devices::query()
            ->with([
                'playPrices.playType',
                'activeSession.latestDetail',
                'activeSession.customer',
                'activeSession.details.user',
                'activeSession.orderSessionDetails.catalogItem',
                'activeSession.orderSessionDetails.user',
            ])
            ->find($this->selectedBusyDeviceId);
    }

    /** @return Collection<int, PlayPrice> */
    public function getAvailableSwitchPlayPricesProperty(): Collection
    {
        $device = $this->selectedBusyDevice;
        if (! $device) {
            return collect();
        }

        $currentPlayTypeName = trim((string) ($device->activeSession?->latestDetail?->play_type ?? ''));

        return $device->playPrices
            ->filter(function ($price) use ($currentPlayTypeName): bool {
                $candidatePlayTypeName = trim((string) ($price->playType?->name ?? ''));

                return $candidatePlayTypeName !== '' && $candidatePlayTypeName !== $currentPlayTypeName;
            })
            ->values();
    }

    /** @return Collection<int, Devices> */
    public function getAvailableSwitchDevicesProperty(): Collection
    {
        $currentDevice = $this->selectedBusyDevice;
        if (! $currentDevice) {
            return collect();
        }

        return Devices::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereKeyNot($currentDevice->id)
            ->with(['type', 'playPrices.playType'])
            ->orderBy('name', 'asc')
            ->get();
    }

    private function refreshBusyClosePreview(): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب' || ! $this->selectedBusyDeviceId) {
            $this->busyClosePreview = null;

            return;
        }

        try {
            $this->busyClosePreview = $this->buildBusyClosePreview((float) $this->busyActionPaidTotal);
        } catch (RuntimeException) {
            $this->busyClosePreview = null;
        }
    }

    /** @return array<string,mixed>|null */
    private function buildBusyClosePreview(float $paidAmount): ?array
    {
        if (! $this->selectedBusyDeviceId) {
            return null;
        }

        return app(CloseSessionInvoiceAction::class)->preview(
            (int) $this->selectedBusyDeviceId,
            (float) ($this->busyActionDiscountAmount ?? 0),
            $paidAmount,
        );
    }

    /** @return Collection<int, CashBox> */
    public function getBusyPaymentCashBoxesProperty(): Collection
    {
        return CashBox::query()
            ->where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();
    }

    /** @return Collection<int, Customer> */
    public function getBusyFilteredCustomersProperty(): Collection
    {
        $query = Customer::query()->where('is_active', true);

        $term = trim($this->busyActionCustomerSearch);
        if ($term !== '') {
            $query->where(function ($subQuery) use ($term): void {
                $subQuery->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderBy('name', 'asc')
            ->limit(50)
            ->get();
    }

    public function getBusySelectedCustomerProperty(): ?Customer
    {
        if (! is_numeric($this->busyActionCustomerId)) {
            return null;
        }

        return Customer::query()->find((int) $this->busyActionCustomerId);
    }

    public function getBusyActionPaidTotalProperty(): float
    {
        return round((float) collect($this->busyActionPayments)
            ->sum(static fn (array $row): float => max(0, (float) ($row['amount'] ?? 0))), 2);
    }

    public function addBusyActionPaymentRow(): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return;
        }

        $this->busyActionPayments[] = [
            'cash_box_id' => null,
            'amount' => null,
        ];

        $this->busyCloseDepositRemainderToCustomerBalance = false;
        $this->refreshBusyClosePreview();
    }

    public function removeBusyActionPaymentRow(int $index): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return;
        }

        if (! array_key_exists($index, $this->busyActionPayments)) {
            return;
        }

        if (count($this->busyActionPayments) === 1) {
            $this->busyActionPayments[0] = [
                'cash_box_id' => null,
                'amount' => 0.0,
            ];
        } else {
            unset($this->busyActionPayments[$index]);
            $this->busyActionPayments = array_values($this->busyActionPayments);
        }

        $this->syncSingleClosePaymentAmount();
        $this->syncBusyCloseDepositToggle();
        $this->refreshBusyClosePreview();
    }

    public function updatedBusyActionDiscountAmount(): void
    {
        $this->syncSingleClosePaymentAmount();
        $this->syncBusyCloseDepositToggle();
        $this->refreshBusyClosePreview();
    }

    public function updatedBusyActionPayments(): void
    {
        $this->busyActionPaidAmount = $this->busyActionPaidTotal;
        $this->syncBusyCloseDepositToggle();
        $this->refreshBusyClosePreview();
    }

    public function selectBusyActionCustomer(int $customerId): void
    {
        $customer = Customer::query()
            ->where('is_active', true)
            ->find($customerId);

        if (! $customer) {
            return;
        }

        $this->busyActionCustomerId = $customer->id;
        $this->busyActionCustomerSearch = $customer->name;
        $this->syncBusyCloseDepositToggle();

        $session = $this->selectedBusyDevice?->activeSession;
        if ($session) {
            $session->update(['customer_id' => $customer->id]);
            unset($this->selectedBusyDevice);

            Notification::make()
                ->title('تم تعيين العميل للجلسة')
                ->body('تم تعيين العميل '.$customer->name.' للجلسة بنجاح.')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('customer_assigned');
        }
    }

    public function clearBusyActionCustomer(): void
    {
        $this->busyActionCustomerId = null;
        $this->busyActionCustomerSearch = '';
        $this->busyCloseDepositRemainderToCustomerBalance = false;

        $session = $this->selectedBusyDevice?->activeSession;
        if ($session) {
            $session->update(['customer_id' => null]);
            unset($this->selectedBusyDevice);

            Notification::make()
                ->title('تم إلغاء تعيين العميل')
                ->body('تم إزالة العميل من الجلسة.')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('customer_unassigned');
        }
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
        if (! $this->selectedOrderGroupId) {
            return collect();
        }

        return CatalogItem::query()
            ->where('is_active', true)
            ->where('inventory_group_id', $this->selectedOrderGroupId)
            ->orderBy('name', 'asc')
            ->get();
    }

    public function getSelectedOrderItemProperty(): ?CatalogItem
    {
        if (! $this->selectedCatalogItemId) {
            return null;
        }

        return CatalogItem::query()->find($this->selectedCatalogItemId);
    }

    public function selectOrderGroup(int $groupId): void
    {
        $groupExists = InventoryGroup::query()
            ->where('is_active', true)
            ->whereKey($groupId)
            ->exists();

        if (! $groupExists) {
            return;
        }

        $this->selectedOrderGroupId = $groupId;
        $this->selectedCatalogItemId = null;
        $this->orderItemQuantity = 1;
        $this->orderItemNotes = null;
    }

    public function openOrderItemQuantityModal(int $catalogItemId): void
    {
        if ($this->selectedBusyActionLabel !== 'إضافة طلبات') {
            return;
        }

        $itemExists = CatalogItem::query()
            ->where('is_active', true)
            ->whereKey($catalogItemId)
            ->when($this->selectedOrderGroupId, function ($query): void {
                $query->where('inventory_group_id', $this->selectedOrderGroupId);
            })
            ->exists();

        if (! $itemExists) {
            Notification::make()->title('الصنف غير متاح')->warning()->send();

            return;
        }

        $this->selectedCatalogItemId = $catalogItemId;
        $this->orderItemQuantity = 1;
        $this->orderItemNotes = null;
    }

    public function closeOrderItemQuantityModal(): void
    {
        $this->selectedCatalogItemId = null;
        $this->orderItemQuantity = 1;
        $this->orderItemNotes = null;
    }

    public function addOrderItemToBusySession(): void
    {
        if ($this->selectedBusyActionLabel !== 'إضافة طلبات') {
            return;
        }

        if (! $this->selectedBusyDeviceId || ! $this->selectedCatalogItemId) {
            Notification::make()->title('بيانات الطلب غير مكتملة')->warning()->send();

            return;
        }

        if (! is_numeric($this->orderItemQuantity) || (int) $this->orderItemQuantity <= 0) {
            Notification::make()->title('يرجى إدخال كمية صحيحة')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        /** @var PlaySessions|null $session */
        $session = PlaySessions::query()
            ->where('device_id', $this->selectedBusyDeviceId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (! $session) {
            Notification::make()->title('لا توجد جلسة نشطة لهذا الجهاز')->danger()->send();

            return;
        }

        /** @var CatalogItem|null $item */
        $item = CatalogItem::query()
            ->where('is_active', true)
            ->whereKey($this->selectedCatalogItemId)
            ->first();

        if (! $item) {
            Notification::make()->title('الصنف غير متاح')->danger()->send();

            return;
        }

        $unitPrice = round((float) ($item->sale_price ?? 0), 2);
        $quantity = (int) $this->orderItemQuantity;
        $total = round($unitPrice * $quantity, 2);

        OrderSessionDetail::create([
            'play_session_id' => $session->id,
            'device_id' => (int) $session->device_id,
            'catalog_item_id' => (int) $item->id,
            'price' => $unitPrice,
            'quantity' => $quantity,
            'total' => $total,
            'status' => 'pending',
            'order_by' => $user->name,
            'user_id' => $user->id,
            'notes' => $this->orderItemNotes,
        ]);

        Notification::make()
            ->title('تمت إضافة الصنف إلى الطلبات')
            ->body($item->name.' x '.$quantity.($this->orderItemNotes ? ' - ملاحظات : '.$this->orderItemNotes : ''))
            ->success()
            ->send();

        $this->broadcastDeviceControlRefresh('order_added');

        $this->closeOrderItemQuantityModal();
    }

    public function addBusySessionAmount(): void
    {
        if ($this->selectedBusyActionLabel !== 'إضافة فلوس') {
            return;
        }

        if (! is_numeric($this->busyActionAmount) || (float) $this->busyActionAmount <= 0) {
            Notification::make()->title('يرجى إدخال مبلغ صحيح أكبر من صفر')->warning()->send();

            return;
        }

        if ((float) $this->busyActionAmount > 999) {
            Notification::make()->title('المبلغ يجب ألا يتجاوز 3 أرقام (999)')->warning()->send();

            return;
        }

        $amount = round((float) $this->busyActionAmount, 2);

        try {
            DB::transaction(function () use ($amount): void {
                $session = PlaySessions::query()
                    ->where('device_id', (int) $this->selectedBusyDeviceId)
                    ->where('status', 'active')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (! $session instanceof PlaySessions) {
                    throw new RuntimeException('لا توجد جلسة نشطة لهذا الجهاز.');
                }

                $session->update([
                    'prepaid_amount' => round((float) ($session->prepaid_amount ?? 0) + $amount, 2),
                ]);
            });
        } catch (RuntimeException $e) {
            Notification::make()->title('فشل إضافة المبلغ')->body($e->getMessage())->danger()->send();

            return;
        }

        $session = PlaySessions::query()
            ->where('device_id', $this->selectedBusyDeviceId)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        Notification::make()
            ->title('تمت إضافة المبلغ بنجاح')
            ->body('الرصيد المقدم الحالي: '.number_format((float) ($session?->prepaid_amount ?? 0), 2))
            ->success()
            ->send();

        $this->broadcastDeviceControlRefresh('amount_added');

        $this->closeBusyActionDetailsModal();
    }

    public function addBusySessionTime(): void
    {
        if ($this->selectedBusyActionLabel !== 'إضافة وقت') {
            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($this->selectedBusyDevice)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن إضافة وقت للجهاز.')
                ->warning()
                ->send();

            return;
        }

        if (! is_numeric($this->busyActionMinutes) || (int) $this->busyActionMinutes <= 0) {
            Notification::make()->title('يرجى إدخال عدد دقائق صحيح أكبر من صفر')->warning()->send();

            return;
        }

        if ((int) $this->busyActionMinutes > 999) {
            Notification::make()->title('عدد الدقائق يجب ألا يتجاوز 3 أرقام (999)')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            app(AddSessionTimeAction::class)->execute(
                (int) $this->selectedBusyDeviceId,
                (int) $this->busyActionMinutes,
                $user,
            );

            Notification::make()
                ->title('تم إضافة الوقت بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('time_added');

            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل إضافة الوقت')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function switchBusySessionPlay(): void
    {
        if ($this->selectedBusyActionLabel !== 'تبديل اللعب') {
            return;
        }

        if (! $this->selectedBusyDeviceId || ! is_numeric($this->selectedSwitchPlayPriceId)) {
            Notification::make()->title('اختر نوع اللعب الجديد أولاً')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            app(SwitchPlayAction::class)->execute(
                (int) $this->selectedBusyDeviceId,
                (int) $this->selectedSwitchPlayPriceId,
                $user,
            );

            Notification::make()
                ->title('تم تبديل نوع اللعب بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('play_switched');

            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل تبديل اللعب')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function switchBusySessionDevice(): void
    {
        if ($this->selectedBusyActionLabel !== 'تبديل أجهزة') {
            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($this->selectedBusyDevice)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن تبديل الجهاز.')
                ->warning()
                ->send();

            return;
        }

        if (! $this->selectedBusyDeviceId || ! is_numeric($this->selectedSwitchDeviceId)) {
            Notification::make()->title('اختر الجهاز الجديد أولاً')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            app(SwitchDeviceAction::class)->execute(
                (int) $this->selectedBusyDeviceId,
                (int) $this->selectedSwitchDeviceId,
                $user,
            );

            Notification::make()
                ->title('تم تبديل الجهاز بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('device_switched');

            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل تبديل الجهاز')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function closeBusySessionInvoice(): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($this->selectedBusyDevice)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن تقفيل الحساب للجهاز.')
                ->warning()
                ->send();

            return;
        }

        if (! is_numeric($this->busyActionDiscountAmount) || (float) $this->busyActionDiscountAmount < 0) {
            Notification::make()->title('يرجى إدخال قيمة خصم صحيحة')->warning()->send();

            return;
        }

        if ((float) $this->busyActionDiscountAmount > 999) {
            Notification::make()->title('قيمة الخصم يجب ألا تتجاوز 3 أرقام (999)')->warning()->send();

            return;
        }

        if ((float) $this->busyActionDiscountAmount > 0 && trim((string) $this->busyActionDiscountReason) === '') {
            Notification::make()->title('سبب الخصم إجباري عند إدخال خصم')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        $paymentAllocations = [];
        foreach ($this->busyActionPayments as $index => $paymentRow) {
            $amount = round((float) ($paymentRow['amount'] ?? 0), 2);
            $cashBoxId = (int) ($paymentRow['cash_box_id'] ?? 0);

            if ($amount <= 0) {
                continue;
            }

            if ($cashBoxId <= 0) {
                Notification::make()->title('اختر خزينة صحيحة لكل دفعة')->body('سطر رقم '.($index + 1))->warning()->send();

                return;
            }

            $paymentAllocations[] = [
                'cash_box_id' => $cashBoxId,
                'amount' => $amount,
            ];
        }

        $paidAmount = round((float) collect($paymentAllocations)->sum('amount'), 2);

        try {
            $invoice = app(CloseSessionInvoiceAction::class)->execute(
                (int) $this->selectedBusyDeviceId,
                $user,
                $paidAmount,
                (float) $this->busyActionDiscountAmount,
                $this->busyActionDiscountReason,
                $paymentAllocations,
                is_numeric($this->busyActionCustomerId) ? (int) $this->busyActionCustomerId : null,
                $this->busyCloseDepositRemainderToCustomerBalance,
            );

            $cashBoxName = trim((string) ($invoice->cashBox?->name ?? ''));
            if ($cashBoxName === '') {
                $cashBoxName = 'غير محددة';
            }

            Notification::make()
                ->title('تم تقفيل الحساب بنجاح')
                ->body('رقم الفاتورة: '.$invoice->invoice_no.' | الخزينة: '.$cashBoxName)
                ->success()
                ->send();

            if ($this->isPlayInvoicePrintingEnabled()) {
                $this->dispatch('open-print-window', url: route('tenant.invoices.print', ['invoice' => $invoice->id, 'silent' => 1]));
            }

            // Send WhatsApp invoice if enabled via Queue
            SendInvoiceWhatsAppJob::dispatch(
                tenant()->getTenantKey(),
                (int) $invoice->id,
                $this->busyActionWhatsappPhone ?: null
            );
            $this->busyActionWhatsappPhone = '';

            $this->broadcastDeviceControlRefresh('invoice_closed');

            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            $this->lastCloseInvoiceError = $e->getMessage();
            $this->lastCloseInvoiceDeviceName = $this->selectedBusyDevice?->name ?? 'غير معروف';

            Notification::make()
                ->title('فشل تقفيل الحساب')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cancelBusySessionOrder(): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return;
        }

        if (! $this->selectedBusyDeviceId) {
            Notification::make()->title('لا يوجد جهاز محدد')->warning()->send();

            return;
        }

        if ($this->bridgeUnavailable && $this->isBridgeRequiredForDevice($this->selectedBusyDevice)) {
            Notification::make()
                ->title('غير متاح')
                ->body('نظام التحكم متوقف حالياً. لا يمكن إلغاء الأوردر بدون Bridge.')
                ->warning()
                ->send();

            return;
        }

        if (! $this->canCancelBusySessionOrder) {
            Notification::make()
                ->title('انتهى وقت السماح')
                ->body('لا يمكن إلغاء الأوردر بعد تجاوز وقت السماح المحدد من بداية الجلسة.')
                ->danger()
                ->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            DB::transaction(function () use ($user): void {
                /** @var Devices $device */
                $device = Devices::query()
                    ->whereKey((int) $this->selectedBusyDeviceId)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $device instanceof Devices) {
                    throw new RuntimeException('تعذر تحميل بيانات الجهاز.');
                }

                if ($device->status !== 'busy') {
                    throw new RuntimeException('الجهاز غير مشغول حالياً.');
                }

                /** @var PlaySessions|null $session */
                $session = PlaySessions::query()
                    ->where('device_id', $device->id)
                    ->where('status', 'active')
                    ->with(['details' => fn ($query) => $query->orderBy('id', 'asc')])
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();

                if (! $session instanceof PlaySessions) {
                    throw new RuntimeException('لا توجد جلسة نشطة لهذا الجهاز.');
                }

                $startTime = $session->getOriginalStartTime();
                if (! $startTime) {
                    throw new RuntimeException('لا يمكن تحديد وقت بداية الجلسة.');
                }

                $cancelWindowMinutes = $this->getCancelOrderAllowedMinutes();
                if ($cancelWindowMinutes <= 0) {
                    throw new RuntimeException('إلغاء الأوردر غير مفعل حالياً.');
                }

                $elapsedMinutes = (int) max(0, $startTime->diffInMinutes(now()));
                if ($elapsedMinutes > $cancelWindowMinutes) {
                    throw new RuntimeException('لقد تجاوزت وقت السماح لإلغاء الأوردر.');
                }

                app(DispatchDeviceControlCommandAction::class)->execute($device, $user, 'OFF', 'Cancel Session Order');

                $device->update(['status' => 'active']);

                OrderSessionDetail::query()
                    ->where('play_session_id', $session->id)
                    ->update(['status' => 'cancelled']);

                PlaySessions::query()->whereKey($session->id)->delete();
            });

            Notification::make()
                ->title('تم إلغاء الأوردر والجلسة بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('order_cancelled');
            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل إلغاء الأوردر')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getCanCancelBusySessionOrderProperty(): bool
    {
        if (! $this->selectedBusyDeviceId || $this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return false;
        }

        $cancelWindowMinutes = $this->getCancelOrderAllowedMinutes();
        if ($cancelWindowMinutes <= 0) {
            return false;
        }

        $elapsedMinutes = $this->busySessionElapsedMinutes;
        if ($elapsedMinutes === null) {
            return false;
        }

        return $elapsedMinutes <= $cancelWindowMinutes;
    }

    public function getBusySessionElapsedMinutesProperty(): ?int
    {
        $session = $this->selectedBusyDevice?->activeSession;
        if (! $session) {
            return null;
        }

        $startTime = $session->getOriginalStartTime();
        if (! $startTime) {
            return null;
        }

        return (int) max(0, $startTime->diffInMinutes(now()));
    }

    public function getBusyCancelOrderRemainingMinutesProperty(): ?int
    {
        $cancelWindowMinutes = $this->getCancelOrderAllowedMinutes();
        $elapsedMinutes = $this->busySessionElapsedMinutes;

        if ($cancelWindowMinutes <= 0 || $elapsedMinutes === null) {
            return null;
        }

        return max(0, $cancelWindowMinutes - $elapsedMinutes);
    }

    public function getCancelOrderAllowedMinutesProperty(): int
    {
        return $this->getCancelOrderAllowedMinutes();
    }

    private function getCancelOrderAllowedMinutes(): int
    {
        if ($this->cancelOrderAllowedMinutesCache !== null) {
            return $this->cancelOrderAllowedMinutesCache;
        }

        $this->cancelOrderAllowedMinutesCache = max(0, (int) SystemConfig::get('sys_cancel_order_time', 0));

        return $this->cancelOrderAllowedMinutesCache;
    }

    public function limitBusySessionTime(): void
    {
        if ($this->selectedBusyActionLabel !== 'تحديد الوقت') {
            return;
        }

        if (! is_numeric($this->busyActionLimitMinutes) || (int) $this->busyActionLimitMinutes <= 0) {
            Notification::make()->title('يرجى إدخال عدد دقائق صحيح أكبر من صفر')->warning()->send();

            return;
        }

        if ((int) $this->busyActionLimitMinutes > 999) {
            Notification::make()->title('عدد الدقائق يجب ألا يتجاوز 3 أرقام (999)')->warning()->send();

            return;
        }

        try {
            app(LimitSessionTimeAction::class)->execute(
                (int) $this->selectedBusyDeviceId,
                (int) $this->busyActionLimitMinutes,
            );

            Notification::make()
                ->title('تم تحديد الوقت بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('time_limited');

            $this->closeBusyActionDetailsModal();
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل تحديد الوقت')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        if (! is_numeric($this->selectedCustomerId)) {
            return null;
        }

        return Customer::query()->find((int) $this->selectedCustomerId);
    }

    public function selectCustomer(int $customerId): void
    {
        $customer = Customer::query()
            ->where('is_active', true)
            ->find($customerId);

        if (! $customer) {
            return;
        }

        $this->selectedCustomerId = $customer->id;
        $this->customerSearch = $customer->name;
    }

    public function clearCustomerSelection(): void
    {
        $this->selectedCustomerId = null;
        $this->customerSearch = '';
    }

    private function syncSingleClosePaymentAmount(): void
    {
        if ($this->selectedBusyActionLabel !== 'تقفيل الحساب') {
            return;
        }

        if (count($this->busyActionPayments) !== 1) {
            return;
        }

        $finalAmount = (float) ($this->busyClosePreview['final_amount'] ?? 0);
        $normalizedFinalAmount = round(max(0, $finalAmount), 2);

        if ($this->busyActionPayments[0]['amount'] === null || $this->busyActionPayments[0]['amount'] === '') {
            $this->busyActionPayments[0]['amount'] = $normalizedFinalAmount;
            $this->busyActionPaidAmount = $normalizedFinalAmount;
        }
    }

    private function syncBusyCloseDepositToggle(): void
    {
        if (! is_numeric($this->busyActionCustomerId)) {
            $this->busyCloseDepositRemainderToCustomerBalance = false;

            return;
        }

        $remainAmount = (float) ($this->busyClosePreview['remain_amount'] ?? 0);
        if ($remainAmount <= 0) {
            $this->busyCloseDepositRemainderToCustomerBalance = false;
        }
    }

    private function isPlayInvoicePrintingEnabled(): bool
    {
        return in_array((string) SystemConfig::get('print_active', '0'), ['1', 'true', 'on', 'yes'], true);
    }

    public function hasReservationToday(): bool
    {
        $deviceId = $this->selectedDeviceId ?: $this->pendingStartDeviceId;
        if (! $deviceId) {
            return false;
        }

        return Reservation::query()
            ->where('device_id', $deviceId)
            ->where('start_time', '>=', Carbon::today())
            ->where('start_time', '<=', Carbon::today()->endOfDay())
            ->where('status', 'active')
            ->exists();
    }

    public function getTodayReservations(): Collection
    {
        $deviceId = $this->selectedDeviceId ?: $this->pendingStartDeviceId;
        if (! $deviceId) {
            return collect();
        }

        return Reservation::query()
            ->where('device_id', $deviceId)
            ->where('start_time', '>=', Carbon::today())
            ->where('start_time', '<=', Carbon::today()->endOfDay())
            ->where('status', 'active')
            ->with(['customer', 'playPrice.playType'])
            ->orderBy('start_time')
            ->get();
    }

    public function checkReservationBeforeStart(int $deviceId, int $playPriceId): void
    {
        $hasReservation = Reservation::query()
            ->where('device_id', $deviceId)
            ->where('start_time', '>=', Carbon::today())
            ->where('start_time', '<=', Carbon::today()->endOfDay())
            ->where('status', 'active')
            ->exists();

        if ($hasReservation) {
            $this->pendingStartDeviceId = $deviceId;
            $this->pendingStartPlayPriceId = $playPriceId;
            $this->showReservationAlertModal = true;
        } else {
            $this->startSession($deviceId, $playPriceId);
        }
    }

    public function confirmPendingStartSession(): void
    {
        if ($this->pendingStartDeviceId && $this->pendingStartPlayPriceId) {
            $this->startSession($this->pendingStartDeviceId, $this->pendingStartPlayPriceId);
        }
        $this->closeReservationAlertModal();
    }

    public function closeReservationAlertModal(): void
    {
        $this->showReservationAlertModal = false;
        $this->pendingStartDeviceId = null;
        $this->pendingStartPlayPriceId = null;
    }

    public function startSessionFromModal(int $playPriceId): void
    {
        if (! $this->selectedDeviceId) {
            Notification::make()->title('يرجى اختيار جهاز أولاً')->warning()->send();

            return;
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        $durationMinutes = is_numeric($this->selectedDurationMinutes)
            ? (int) $this->selectedDurationMinutes
            : null;

        if (is_numeric($this->customDurationMinutes) && (int) $this->customDurationMinutes > 0) {
            $durationMinutes = (int) $this->customDurationMinutes;
        }

        $customerId = is_numeric($this->selectedCustomerId)
            ? (int) $this->selectedCustomerId
            : null;

        try {
            app(StartSessionAction::class)->execute(
                $this->selectedDeviceId,
                $playPriceId,
                $user,
                $durationMinutes,
                $customerId,
            );

            $this->closeStartSessionModal();

            Notification::make()
                ->title('تم فتح الجلسة بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('session_started');
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل فتح الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function startSession(int $deviceId, int $playPriceId): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user instanceof User) {
            Notification::make()->title('غير مصرح')->danger()->send();

            return;
        }

        try {
            app(StartSessionAction::class)->execute($deviceId, $playPriceId, $user, null, null);

            Notification::make()
                ->title('تم فتح الجلسة بنجاح')
                ->success()
                ->send();

            $this->broadcastDeviceControlRefresh('session_started');
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('فشل فتح الجلسة')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function turnOffDeviceViaBridge(int $deviceId): void
    {
        $device = Devices::find($deviceId);
        if ($device && $device->status === 'busy') {
            $isControlActive = in_array(
                (string) SystemConfig::get('sys_control_active', '0'),
                ['1', 'true', 'on', 'yes'],
                true
            );
            $ipAddress = trim((string) ($device->ip_address ?? ''));
            $isRealBridgeDevice = $isControlActive && $device->is_time && $ipAddress !== '' && $ipAddress !== '127.0.0.1';

            try {
                app(DispatchDeviceControlCommandAction::class)->execute(
                    $device,
                    Auth::user(),
                    'OFF',
                    'Time Finished (Auto)'
                );

                if ($isRealBridgeDevice) {
                    Notification::make()
                        ->title('تم إيقاف تشغيل الجهاز تلقائياً')
                        ->body('انتهى الوقت المحدد لجهاز: '.$device->name)
                        ->success()
                        ->send();
                }

                $this->broadcastDeviceControlRefresh('Turn Off Device (Auto)');
            } catch (\Throwable $e) {
                if ($isRealBridgeDevice) {
                    Notification::make()
                        ->title('خطأ: الجهاز غير متصل على الشبكة')
                        ->body('جهاز "'.$device->name.'" غير متصل على الشبكة. يرجى التأكد من توصيله بالكهرباء والشبكة.')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            }
        }
    }

    public static function getNavigationBadge(): ?string
    {
        $busy = Devices::query()->where('status', 'busy')->count('*');

        return $busy > 0 ? (string) $busy : null;
    }

    public function getRealtimeDeviceControlChannel(): string
    {
        return 'tenant.'.$this->getTenantBroadcastKey().'.device-control';
    }

    private function broadcastDeviceControlRefresh(string $action): void
    {
        try {
            event(new DeviceControlUpdated(
                tenantKey: $this->getTenantBroadcastKey(),
                action: $action,
            ));
        } catch (\Throwable $e) {
            Log::warning('Reverb broadcasting failed: '.$e->getMessage());
        }
    }

    private function getTenantBroadcastKey(): string
    {
        $tenant = tenant();

        return $tenant ? (string) $tenant->getTenantKey() : 'central';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
