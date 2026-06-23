<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $guarded = [];

    protected $fillable = [
        'user_id',
        'plan_id',
        'started_at',
        'ended_at',
        'auto_renew',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'user_id',
            'plan_id',
            'started_at',
            'ended_at',
            'auto_renew',
            'is_active',
        ];
    }

    /**
     * Check if subscription is expired
     */
    public function isSubscriptionExpired(): bool
    {
        if (! $this->ended_at) {
            return false;
        }

        return Carbon::now()->isAfter($this->ended_at);
    }

    /**
     * Check if subscription is active
     */
    public function hasActiveSubscription(): bool
    {
        return $this->is_active && ! $this->isSubscriptionExpired();
    }

    /**
     * Get remaining days
     */
    public function remainingDays(): int
    {
        if (! $this->ended_at) {
            return 0;
        }

        return (int) max(0, Carbon::now()->diffInDays($this->ended_at, false));
    }

    /**
     * Start a new subscription
     */
    public function startSubscription(?Carbon $startsAt = null): void
    {
        $periodStart = $startsAt?->copy() ?? Carbon::now();

        $this->started_at = $periodStart;
        $this->ended_at = $periodStart->copy();
        $this->is_active = true;
        $this->save();
    }
}
