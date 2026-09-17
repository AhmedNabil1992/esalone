<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\DeviceControl;
use ReflectionClass;
use Tests\TestCase;

class DeviceControlResponsiveThemeTest extends TestCase
{
    public function test_device_control_has_dark_mode_and_mobile_layout_rules(): void
    {
        $view = file_get_contents(resource_path('views/filament/pages/device-control.blade.php'));
        $styles = file_get_contents(resource_path('views/filament/pages/device-control/styles.blade.php'));
        $closeSession = file_get_contents(resource_path('views/filament/pages/device-control/actions/close-session.blade.php'));
        $addOrders = file_get_contents(resource_path('views/filament/pages/device-control/actions/add-orders.blade.php'));
        $deviceDetails = file_get_contents(resource_path('views/filament/pages/device-control/actions/device-details.blade.php'));
        $detailsModal = file_get_contents(resource_path('views/filament/pages/device-control/busy-action-details-modal.blade.php'));
        $deviceGrid = file_get_contents(resource_path('views/filament/pages/device-control/overview/device-grid.blade.php'));

        $this->assertIsString($view);
        $this->assertIsString($styles);
        $this->assertIsString($closeSession);
        $this->assertIsString($addOrders);
        $this->assertIsString($deviceDetails);
        $this->assertIsString($detailsModal);
        $this->assertIsString($deviceGrid);
        $this->assertStringContainsString('class="device-control-page"', $view);
        $this->assertStringContainsString('.dark .device-control-page [style*="background:#fff"]', $styles);
        $this->assertStringContainsString('.dark .device-control-modal-panel', $styles);
        $this->assertStringContainsString('.dark .device-control-modal-panel .bg-white', $styles);
        $this->assertStringContainsString('@media (max-width: 640px)', $styles);
        $this->assertStringContainsString('max-height: 100dvh !important;', $styles);
        $this->assertStringContainsString('class="device-control-close-grid"', $closeSession);
        $this->assertStringContainsString('class="device-control-payment-row"', $closeSession);
        $this->assertStringContainsString('device-control-close-error', $closeSession);
        $this->assertStringContainsString('wire:model.live.debounce.250ms="orderItemSearch"', $addOrders);
        $this->assertStringContainsString('class="device-control-order-items-grid"', $addOrders);
        $this->assertStringContainsString("'min(96vw, 1040px)'", $detailsModal);
        $this->assertStringContainsString('grid-template-columns: repeat(auto-fill, minmax(125px, 1fr));', $styles);
        $this->assertStringContainsString('updateBusySessionOrderQuantity', $deviceDetails);
        $this->assertStringContainsString('deleteBusySessionOrder', $deviceDetails);
        $this->assertStringContainsString('updateBusySessionOrderQuantity', $closeSession);
        $this->assertStringContainsString('deleteBusySessionOrder', $closeSession);
        $this->assertStringContainsString('@if ($this->canApplyDeviceControlDiscount)', $closeSession);
        $this->assertStringContainsString('clockNow: Math.floor(Date.now() / 1000)', $view);
        $this->assertStringContainsString('formatDuration(totalSeconds)', $view);
        $this->assertStringContainsString('x-text="formatDuration(Math.max(0, clockNow -', $deviceGrid);
        $this->assertStringContainsString('x-text="formatDuration(Math.max(0,', $deviceGrid);
    }

    public function test_device_control_is_composed_from_focused_partials(): void
    {
        $view = file_get_contents(resource_path('views/filament/pages/device-control.blade.php'));
        $detailsModal = file_get_contents(resource_path('views/filament/pages/device-control/busy-action-details-modal.blade.php'));

        $this->assertIsString($view);
        $this->assertIsString($detailsModal);

        foreach ([
            'styles',
            'device-overview',
            'busy-actions-modal',
            'busy-action-details-modal',
            'start-session-modal',
            'printing-script',
            'reservation-alert-modal',
        ] as $partial) {
            $this->assertStringContainsString("filament.pages.device-control.{$partial}", $view);
        }

        foreach ([
            'close-session',
            'add-money',
            'add-orders',
            'device-details',
            'switch-play',
            'switch-device',
            'limit-time',
            'add-time',
        ] as $actionPartial) {
            $this->assertStringContainsString("filament.pages.device-control.actions.{$actionPartial}", $detailsModal);
        }
    }

    public function test_order_management_is_exposed_in_current_orders_report(): void
    {
        $table = file_get_contents(app_path('Filament/Resources/OrderSessionDetails/Tables/OrderSessionDetailsTable.php'));

        $this->assertIsString($table);
        $this->assertStringContainsString("Action::make('update_quantity')", $table);
        $this->assertStringContainsString("Action::make('delete_order')", $table);
        $this->assertStringContainsString('ManageSessionOrderAction::UPDATE_PERMISSION', $table);
        $this->assertStringContainsString('ManageSessionOrderAction::DELETE_PERMISSION', $table);
    }

    public function test_device_control_custom_permissions_have_arabic_labels(): void
    {
        $permissions = config('filament-shield.custom_permissions');

        $this->assertSame('تعديل كمية الطلبات', $permissions['Update:DeviceControl']);
        $this->assertSame('حذف الطلبات', $permissions['Delete:DeviceControl']);
        $this->assertSame('إضافة خصم لفواتير البيع', $permissions['Discount:DeviceControl']);
    }

    public function test_all_split_view_livewire_bindings_still_target_public_component_api(): void
    {
        $views = file_get_contents(resource_path('views/filament/pages/device-control.blade.php'));
        $partialFiles = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                resource_path('views/filament/pages/device-control'),
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($partialFiles as $partialFile) {
            if ($partialFile->getExtension() === 'php') {
                $views .= file_get_contents($partialFile->getPathname());
            }
        }

        $component = new ReflectionClass(DeviceControl::class);

        preg_match_all('/wire:click(?:\.[\w-]+)*="([A-Za-z_$][A-Za-z0-9_$]*)/', $views, $clickMatches);
        foreach (array_unique($clickMatches[1]) as $method) {
            if (str_starts_with($method, '$')) {
                continue;
            }

            $this->assertTrue($component->hasMethod($method), "Missing Livewire action method [{$method}].");
            $this->assertTrue($component->getMethod($method)->isPublic(), "Livewire action [{$method}] must remain public.");
        }

        preg_match_all('/wire:model(?:\.[\w-]+)*="([^".]+)(?:\.[^"]+)?"/', $views, $modelMatches);
        foreach (array_unique($modelMatches[1]) as $property) {
            $this->assertTrue($component->hasProperty($property), "Missing Livewire state property [{$property}].");
            $this->assertTrue($component->getProperty($property)->isPublic(), "Livewire state [{$property}] must remain public.");
        }
    }
}
