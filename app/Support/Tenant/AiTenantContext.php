<?php

declare(strict_types=1);

namespace App\Support\Tenant;

use App\Models\Tenant\SystemConfig;
use Carbon\CarbonImmutable;
use Throwable;

class AiTenantContext
{
    public function __construct(private readonly TenantClock $clock) {}

    public function currency(): string
    {
        try {
            $currency = strtoupper(trim((string) SystemConfig::get('sys_locale_currency', 'EGP')));

            return $currency !== '' ? $currency : 'EGP';
        } catch (Throwable) {
            return 'EGP';
        }
    }

    public function today(): CarbonImmutable
    {
        return $this->clock->nowLocal()->startOfDay();
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function currentWeek(): array
    {
        $start = $this->today()->startOfWeek(CarbonImmutable::SATURDAY);

        return ['start' => $start, 'end' => $start->addDays(6)];
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public function previousWeek(): array
    {
        $currentWeek = $this->currentWeek();
        $start = $currentWeek['start']->subWeek();

        return ['start' => $start, 'end' => $start->addDays(6)];
    }

    public function promptContext(): string
    {
        $now = $this->clock->nowLocal();
        $today = $this->today();
        $currentWeek = $this->currentWeek();
        $previousWeek = $this->previousWeek();

        return implode("\n", [
            'Tenant timezone: '.$this->clock->timezone(),
            'Tenant local date and time now: '.$now->format('Y-m-d H:i:s P'),
            'Today: '.$today->toDateString(),
            'Yesterday: '.$today->subDay()->toDateString(),
            'Current week (Saturday through Friday): '.$currentWeek['start']->toDateString().' through '.$currentWeek['end']->toDateString(),
            'Previous/last week (Saturday through Friday): '.$previousWeek['start']->toDateString().' through '.$previousWeek['end']->toDateString(),
            'Tenant currency: '.$this->currency(),
        ]);
    }
}
