<?php

namespace App\Models\Tenant;

use App\Scopes\BranchScope;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    use BelongsToBranch;

    protected $table = 'reservations';

    protected $fillable = [
        'branch_id',
        'device_id',
        'customer_id',
        'start_time',
        'play_price_id',
        'end_time',
        'status',
        'duration_minutes',
        'whatsapp_alert_sent',
        'admin_alert_sent',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'whatsapp_alert_sent' => 'boolean',
        'admin_alert_sent' => 'boolean',
    ];

    /** @return BelongsTo<Devices, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<PlayPrice, $this> */
    public function playPrice(): BelongsTo
    {
        return $this->belongsTo(PlayPrice::class);
    }

    public static function checkOverlap(int|string $deviceId, string $startTime, string $endTime, int|string|null $excludeId = null): bool
    {
        return self::withoutGlobalScope(BranchScope::class)
            ->where('device_id', $deviceId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where('status', 'active')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->exists();
    }
}
