<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DeviceControlReviewDeviceTest extends TestCase
{
    public function test_busy_actions_modal_binds_review_button_to_review_busy_device(): void
    {
        $blade = file_get_contents(resource_path('views/filament/pages/device-control/busy-actions-modal.blade.php'));

        $this->assertIsString($blade);
        $this->assertStringContainsString("@elseif (\$action['label'] === 'مراجعة')", $blade);
        $this->assertStringContainsString('wire:click="reviewBusyDevice"', $blade);
    }

    public function test_device_control_contains_review_busy_device_logic(): void
    {
        $page = file_get_contents(app_path('Filament/Pages/DeviceControl.php'));

        $this->assertIsString($page);
        $this->assertStringContainsString('public function reviewBusyDevice(): void', $page);

        // Check fallback in openBusyActionDetails
        $this->assertStringContainsString("if (\$actionLabel === 'مراجعة') {", $page);
        $this->assertStringContainsString('$this->reviewBusyDevice();', $page);

        // Check logic for unlimited session
        $this->assertStringContainsString("\$bridgeAction = 'ON,Unlimited,0';", $page);

        // Check 2-minute threshold check for timed session
        $this->assertStringContainsString('$remainingMinutes <= 2', $page);
        $this->assertStringContainsString("'ON,Limit,'.(string) \$remainingMinutes", $page);

        // Check command dispatch
        $this->assertStringContainsString('app(DispatchDeviceControlCommandAction::class)->execute(', $page);
    }
}
