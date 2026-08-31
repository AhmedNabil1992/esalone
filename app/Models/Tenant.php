<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Tenant\Branch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        'id',
        'user_id',
        'plan_id',
        'started_at',
        'ended_at',
        'auto_renew',
        'is_active',
        'has_ai_subscription',
        'ai_requests_limit',
        'branches_limit',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_active' => 'boolean',
        'has_ai_subscription' => 'boolean',
        'ai_requests_limit' => 'integer',
        'branches_limit' => 'integer',
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
            'has_ai_subscription',
            'ai_requests_limit',
            'branches_limit',
        ];
    }

    /**
     * Get allowed branches limit for this tenant.
     */
    public function getBranchesLimit(): int
    {
        if ($this->branches_limit !== null) {
            return (int) $this->branches_limit;
        }

        if ($this->plan_id) {
            $limit = DB::table('plan_features')
                ->where('plan_id', $this->plan_id)
                ->where('feature', 'branches')
                ->value('limit_value');

            if ($limit !== null) {
                return (int) $limit;
            }
        }

        return 1;
    }

    /**
     * Check if tenant can create a new branch.
     */
    public function canCreateBranch(?int $currentCount = null): bool
    {
        $count = $currentCount ?? Branch::count();

        return $count < $this->getBranchesLimit();
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
