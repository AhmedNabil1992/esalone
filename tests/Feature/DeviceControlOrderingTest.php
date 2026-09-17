<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeviceControlOrderingTest extends TestCase
{
    public function test_devices_are_ordered_by_their_database_sequence_instead_of_their_names(): void
    {
        $page = file_get_contents(app_path('Filament/Pages/DeviceControl.php'));

        $this->assertIsString($page);

        $methodStart = strpos($page, 'public function getDevicesProperty(): Collection');
        $methodEnd = strpos($page, 'public function getCustomersProperty(): Collection', $methodStart);

        $this->assertIsInt($methodStart);
        $this->assertIsInt($methodEnd);

        $method = substr($page, $methodStart, $methodEnd - $methodStart);

        $this->assertStringContainsString("->orderBy('id', 'asc')", $method);
        $this->assertStringNotContainsString("->orderBy('name'", $method);
    }
}
