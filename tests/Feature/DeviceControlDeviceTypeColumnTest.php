<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DeviceControlDeviceTypeColumnTest extends TestCase
{
    public function test_device_name_and_type_precede_play_type_in_both_time_tables(): void
    {
        $invoiceView = file_get_contents(resource_path('views/filament/pages/device-control/actions/close-session.blade.php'));
        $deviceView = file_get_contents(resource_path('views/filament/pages/device-control/actions/device-details.blade.php'));
        $action = file_get_contents(app_path('Actions/Tenant/CloseSessionInvoiceAction.php'));

        $this->assertIsString($invoiceView);
        $this->assertIsString($deviceView);
        $this->assertIsString($action);

        $invoiceTableStart = strpos($invoiceView, 'تفاصيل الوقت');
        $invoiceTableEnd = strpos($invoiceView, 'تفاصيل الطلبات', $invoiceTableStart);
        $invoiceTable = substr($invoiceView, $invoiceTableStart, $invoiceTableEnd - $invoiceTableStart);

        $this->assertLessThan(strpos($invoiceTable, 'اسم الجهاز'), strpos($invoiceTable, 'نوع الجهاز'));
        $this->assertLessThan(strpos($invoiceTable, 'نوع اللعب'), strpos($invoiceTable, 'اسم الجهاز'));
        $this->assertLessThan(strpos($invoiceTable, 'نوع اللعب'), strpos($invoiceTable, 'نوع الجهاز'));
        $this->assertLessThan(strpos($invoiceTable, '$row[\'device_name\']'), strpos($invoiceTable, '$row[\'device_type\']'));
        $this->assertLessThan(strpos($invoiceTable, '$row[\'play_type\']'), strpos($invoiceTable, '$row[\'device_name\']'));
        $this->assertLessThan(strpos($invoiceTable, '$row[\'play_type\']'), strpos($invoiceTable, '$row[\'device_type\']'));

        $deviceTableStart = strpos($deviceView, 'بيانات الوقت');
        $deviceTableEnd = strpos($deviceView, 'بيانات الطلبات', $deviceTableStart);
        $deviceTable = substr($deviceView, $deviceTableStart, $deviceTableEnd - $deviceTableStart);

        $this->assertLessThan(strpos($deviceTable, 'اسم الجهاز'), strpos($deviceTable, 'نوع الجهاز'));
        $this->assertLessThan(strpos($deviceTable, 'نوع اللعب'), strpos($deviceTable, 'اسم الجهاز'));
        $this->assertLessThan(strpos($deviceTable, 'نوع اللعب'), strpos($deviceTable, 'نوع الجهاز'));
        $this->assertLessThan(strpos($deviceTable, '$detail->device?->name'), strpos($deviceTable, '$detail->device?->type?->name'));
        $this->assertLessThan(strpos($deviceTable, '$detail->play_type'), strpos($deviceTable, '$detail->device?->name'));
        $this->assertLessThan(strpos($deviceTable, '$detail->play_type'), strpos($deviceTable, '$detail->device?->type?->name'));
        $this->assertStringContainsString("'device_name' => (string) (\$detail->device?->name ?? '')", $action);
        $this->assertStringContainsString("'device_type' => (string) (\$detail->device?->type?->name ?? '')", $action);
    }
}
