<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class DeviceControlEmptyDiscountTest extends TestCase
{
    public function test_empty_discount_is_normalized_before_discount_validation(): void
    {
        $page = file_get_contents(app_path('Filament/Pages/DeviceControl.php'));

        $this->assertIsString($page);

        $normalizationPosition = strpos($page, "trim((string) \$this->busyActionDiscountAmount) === ''");
        $validationPosition = strpos($page, '! is_numeric($this->busyActionDiscountAmount)');

        $this->assertNotFalse($normalizationPosition);
        $this->assertNotFalse($validationPosition);
        $this->assertLessThan($validationPosition, $normalizationPosition);
        $this->assertStringContainsString('$this->busyActionDiscountAmount = 0;', $page);
    }

    public function test_each_close_attempt_clears_stale_error_and_renders_new_errors_inside_modal(): void
    {
        $page = file_get_contents(app_path('Filament/Pages/DeviceControl.php'));

        $this->assertIsString($page);

        $methodStart = strpos($page, 'public function closeBusySessionInvoice(): void');
        $methodEnd = strpos($page, 'public function cancelBusySessionOrder(): void', $methodStart);
        $method = substr($page, $methodStart, $methodEnd - $methodStart);

        $this->assertStringContainsString('$this->lastCloseInvoiceError = null;', $method);
        $this->assertStringContainsString('$this->lastCloseInvoiceDeviceName = null;', $method);
        $this->assertStringContainsString("\$this->setCloseInvoiceError('اختر خزينة صحيحة للدفعة في السطر رقم '", $method);
        $this->assertStringContainsString('$this->setCloseInvoiceError($e->getMessage());', $method);
        $this->assertSame(1, substr_count($method, 'Notification::make()'));
        $this->assertStringContainsString("->title('تم تقفيل الحساب بنجاح')", $method);
    }
}
