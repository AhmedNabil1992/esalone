<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffMember extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'job_title',
        'phone',
        'email',
        'is_active',
        'weekly_schedule',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weekly_schedule' => 'array',
            'commission_rate' => 'decimal:2',
        ];
    }

    /** @return HasMany<Appointment, $this> */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    /** @return HasMany<StaffCommission, $this> */
    public function commissions(): HasMany
    {
        return $this->hasMany(StaffCommission::class);
    }

    public function isAvailableDuring(CarbonInterface $startsAt, CarbonInterface $endsAt): bool
    {
        $schedule = $this->weekly_schedule ?? [];

        if ($schedule === []) {
            return true;
        }

        if (! $startsAt->isSameDay($endsAt)) {
            return false;
        }

        $dayOfWeek = $startsAt->dayOfWeek;
        $startTime = $startsAt->format('H:i');
        $endTime = $endsAt->format('H:i');

        foreach ($schedule as $slot) {
            if ((int) ($slot['day_of_week'] ?? -1) !== $dayOfWeek) {
                continue;
            }

            $opensAt = $slot['opens_at'] ?? null;
            $closesAt = $slot['closes_at'] ?? null;

            if (! is_string($opensAt)
                || ! is_string($closesAt)
                || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $opensAt)
                || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $closesAt)
                || $opensAt >= $closesAt) {
                continue;
            }

            if ($startTime >= $opensAt && $endTime <= $closesAt) {
                return true;
            }
        }

        return false;
    }
}
