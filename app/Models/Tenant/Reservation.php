<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'device_id',
        'customer_id',
        'start_time',
        'play_price_id',
        'end_time',
        'status',
        'duration_minutes',
        'whatsapp_alert_sent',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'whatsapp_alert_sent' => 'boolean',
    ];

    public function device()
    {
        return $this->belongsTo(Devices::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function playPrice()
    {
        return $this->belongsTo(PlayPrice::class);
    }

    public static function checkOverlap(int|string $deviceId, string $startTime, string $endTime, int|string|null $excludeId = null): bool
    {
        return self::query()
            ->where('device_id', $deviceId)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->where('status', 'active')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '>=', $startTime)
                        ->where('start_time', '<', $endTime);
                })
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('end_time', '>', $startTime)
                            ->where('end_time', '<=', $endTime);
                    })
                    ->orWhere(function ($q) use ($startTime, $endTime) {
                        $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            })
            ->exists();
    }
}
