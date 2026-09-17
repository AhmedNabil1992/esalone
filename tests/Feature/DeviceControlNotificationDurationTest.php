<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DeviceControlNotificationDurationTest extends TestCase
{
    public function test_device_control_persistent_alerts_expire_after_one_minute(): void
    {
        $page = file_get_contents(app_path('Filament/Pages/DeviceControl.php'));

        $this->assertIsString($page);
        $this->assertStringContainsString(
            'private const int NOTIFICATION_DURATION_MILLISECONDS = 60_000;',
            $page,
        );
        $this->assertSame(3, substr_count($page, '->duration(self::NOTIFICATION_DURATION_MILLISECONDS)'));
        $this->assertStringNotContainsString('->persistent()', $page);
    }
}
