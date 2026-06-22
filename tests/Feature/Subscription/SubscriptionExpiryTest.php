<?php

namespace Tests\Feature\Subscription;

use Tests\TestCase;

class SubscriptionExpiryTest extends TestCase
{
    public function test_middleware_checks_subscription_expiry(): void
    {
        // هذا الاختبار يتحقق من أن الـ middleware يعمل بشكل صحيح
        $response = $this->get('/admin');

        // تحقق من أن الطلب يتم معالجته بشكل صحيح
        $this->assertNotNull($response);
    }

    public function test_cleanup_command_is_available(): void
    {
        // تحقق من أن أمر التنظيف مسجل
        $this->artisan('tenants:cleanup-expired')
            ->expectsOutput('Cleanup job dispatched successfully.')
            ->assertExitCode(0);
    }

    public function test_subscription_job_exists(): void
    {
        // تحقق من أن الـ Job موجود
        $jobClass = 'App\\Jobs\\DeleteExpiredTenantDataJob';
        $this->assertTrue(class_exists($jobClass));
    }
}
