<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use App\Models\Tenant\SystemConfig;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

class TenantClock
{
    public const SettingKey = 'sys_system_time_zone';

    public function __construct(private readonly ?string $timezoneOverride = null) {}

    public function timezone(): string
    {
        if ($this->timezoneOverride !== null) {
            return in_array($this->timezoneOverride, DateTimeZone::listIdentifiers(), true)
                ? $this->timezoneOverride
                : 'UTC';
        }

        if (! tenant()) {
            return 'UTC';
        }

        try {
            $timezone = (string) SystemConfig::get(self::SettingKey, 'UTC');

            return in_array($timezone, DateTimeZone::listIdentifiers(), true) ? $timezone : 'UTC';
        } catch (Throwable) {
            return 'UTC';
        }
    }

    public function nowUtc(): CarbonImmutable
    {
        return CarbonImmutable::now('UTC');
    }

    public function nowLocal(): CarbonImmutable
    {
        return $this->nowUtc()->setTimezone($this->timezone());
    }

    public function localToUtc(DateTimeInterface|string $value): CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value)->utc();
        }

        return CarbonImmutable::parse($value, $this->timezone())->utc();
    }

    public function utcToLocal(DateTimeInterface|string $value): CarbonImmutable
    {
        $utc = $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)->utc()
            : CarbonImmutable::parse($value, 'UTC');

        return $utc->setTimezone($this->timezone());
    }

    public function localDateStartUtc(DateTimeInterface|string $date): CarbonImmutable
    {
        return $this->localDateRangeUtc($date)[0];
    }

    public function localDateEndUtc(DateTimeInterface|string $date): CarbonImmutable
    {
        return $this->localDateRangeUtc($date)[1];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function localDateRangeUtc(DateTimeInterface|string $from, DateTimeInterface|string|null $until = null): array
    {
        $timezone = $this->timezone();
        $fromDate = $from instanceof DateTimeInterface
            ? CarbonImmutable::instance($from)->setTimezone($timezone)
            : CarbonImmutable::parse($from, $timezone);
        $untilDate = $until instanceof DateTimeInterface
            ? CarbonImmutable::instance($until)->setTimezone($timezone)
            : CarbonImmutable::parse($until ?? $from, $timezone);

        return [
            $fromDate->startOfDay()->utc(),
            $untilDate->endOfDay()->utc(),
        ];
    }
}
