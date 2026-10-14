<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentService extends Model
{
    protected $fillable = [
        'appointment_id',
        'salon_service_id',
        'service_name',
        'duration_minutes',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<SalonService, $this> */
    public function salonService(): BelongsTo
    {
        return $this->belongsTo(SalonService::class);
    }
}
