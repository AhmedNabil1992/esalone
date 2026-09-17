<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Support\Tenant\AiTenantContext;
use App\Support\Tenant\TenantClock;
use App\Tenancy\Bootstrappers\TimezoneBootstrapper;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantTimezoneTest extends TestCase
{
    public function test_cairo_local_time_is_stored_as_utc_using_the_offset_for_that_date(): void
    {
        Schema::create('timezone_test_events', function (Blueprint $table): void {
            $table->id();
            $table->dateTime('starts_at');
        });

        $clock = new TenantClock('Africa/Cairo');
        $summerUtc = $clock->localToUtc('2026-09-16 22:00:00');
        $winterUtc = $clock->localToUtc('2026-12-16 22:00:00');

        DB::table('timezone_test_events')->insert([
            ['starts_at' => $summerUtc->toDateTimeString()],
            ['starts_at' => $winterUtc->toDateTimeString()],
        ]);

        $this->assertSame('2026-09-16 19:00:00', DB::table('timezone_test_events')->where('id', 1)->value('starts_at'));
        $this->assertSame('2026-12-16 20:00:00', DB::table('timezone_test_events')->where('id', 2)->value('starts_at'));
        $this->assertSame('2026-09-16 22:00:00', $clock->utcToLocal($summerUtc)->toDateTimeString());
        $this->assertSame('2026-12-16 22:00:00', $clock->utcToLocal($winterUtc)->toDateTimeString());
    }

    public function test_session_duration_crossing_local_midnight_is_calculated_by_absolute_instants(): void
    {
        $clock = new TenantClock('Africa/Cairo');
        $start = $clock->localToUtc('2026-12-16 23:00:00');
        $end = $clock->localToUtc('2026-12-17 01:00:00');

        $this->assertSame(120, (int) $start->diffInMinutes($end));
        $this->assertSame('2026-12-16 21:00:00', $start->toDateTimeString());
        $this->assertSame('2026-12-16 23:00:00', $end->toDateTimeString());
    }

    public function test_tenant_bootstrap_does_not_mutate_application_or_php_timezone(): void
    {
        config()->set('app.timezone', 'UTC');
        date_default_timezone_set('UTC');

        $bootstrapper = new TimezoneBootstrapper;
        $bootstrapper->bootstrap(new Tenant);

        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('UTC', date_default_timezone_get());
    }

    public function test_local_day_boundaries_are_converted_to_utc(): void
    {
        $clock = new TenantClock('Africa/Cairo');
        [$start, $end] = $clock->localDateRangeUtc('2026-09-16');

        $this->assertSame('2026-09-15 21:00:00', $start->toDateTimeString());
        $this->assertSame('2026-09-16 20:59:59', $end->toDateTimeString());
    }

    public function test_filament_picker_converts_between_utc_storage_and_cairo_display(): void
    {
        config()->set('app.timezone', 'UTC');
        $cast = new DateTimeStateCast(
            format: 'Y-m-d H:i:s',
            internalFormat: 'Y-m-d H:i:s',
            timezone: 'Africa/Cairo',
        );

        $this->assertSame('2026-09-16 22:00:00', $cast->set('2026-09-16 19:00:00'));
        $this->assertSame('2026-09-16 19:00:00', $cast->get('2026-09-16 22:00:00'));
        $this->assertSame('2026-12-16 22:00:00', $cast->set('2026-12-16 20:00:00'));
        $this->assertSame('2026-12-16 20:00:00', $cast->get('2026-12-16 22:00:00'));
    }

    public function test_ai_context_uses_tenant_local_date_and_saturday_week_boundaries(): void
    {
        Carbon::setTestNow('2026-09-16 20:30:00 UTC');

        try {
            $context = new AiTenantContext(new TenantClock('Africa/Cairo'));
            $prompt = $context->promptContext();

            $this->assertStringContainsString('Tenant local date and time now: 2026-09-16 23:30:00 +03:00', $prompt);
            $this->assertStringContainsString('Today: 2026-09-16', $prompt);
            $this->assertStringContainsString('Yesterday: 2026-09-15', $prompt);
            $this->assertStringContainsString('Current week (Saturday through Friday): 2026-09-12 through 2026-09-18', $prompt);
            $this->assertStringContainsString('Previous/last week (Saturday through Friday): 2026-09-05 through 2026-09-11', $prompt);
            $this->assertStringContainsString('Tenant currency: EGP', $prompt);
        } finally {
            Carbon::setTestNow();
        }
    }
}
