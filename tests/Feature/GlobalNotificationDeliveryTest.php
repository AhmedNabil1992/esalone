<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class GlobalNotificationDeliveryTest extends TestCase
{
    public function test_device_control_uses_a_full_page_visit_and_global_alerts_initialize_once(): void
    {
        $provider = file_get_contents(app_path('Providers/Filament/AdminPanelProvider.php'));
        $audioHook = file_get_contents(resource_path('views/filament/hooks/global-notification-audio.blade.php'));

        $this->assertIsString($provider);
        $this->assertIsString($audioHook);
        $this->assertStringContainsString("'*/admin/device-control*'", $provider);
        $this->assertStringContainsString('__psGlobalNotificationAudioInitialized', $audioHook);
        $this->assertStringContainsString('.fi-topbar-database-notifications-btn .fi-badge', $audioHook);
        $this->assertStringContainsString("document.addEventListener('livewire:navigated'", $audioHook);
    }

    public function test_scheduled_alerts_dispatch_the_realtime_database_notification_event(): void
    {
        $bookingCommand = file_get_contents(app_path('Console/Commands/Tenant/SendBookingAlertsCommand.php'));
        $expiryCommand = file_get_contents(app_path('Console/Commands/Tenant/SendSessionExpiryAlertsCommand.php'));

        $this->assertIsString($bookingCommand);
        $this->assertIsString($expiryCommand);
        $this->assertStringContainsString('sendToDatabase($users, isEventDispatched: true)', $bookingCommand);
        $this->assertSame(2, substr_count($expiryCommand, 'sendToDatabase($users, isEventDispatched: true)'));
    }
}
