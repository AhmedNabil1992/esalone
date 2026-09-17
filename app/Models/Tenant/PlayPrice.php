<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayPrice extends Model
{
    protected $fillable = [
        'play_type_id',
        'device_id',
        'description',
        'price',
        'start_from',
    ];

    /** @return BelongsTo<PlayType, $this> */
    public function playType(): BelongsTo
    {
        return $this->belongsTo(PlayType::class, 'play_type_id');
    }

    /** @return BelongsTo<Devices, $this> */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class, 'device_id');
    }

    /** @return BelongsTo<DevicesType, $this> */
    public function deviceType(): BelongsTo
    {
        return $this->belongsTo(DevicesType::class, 'device_type_id');
    }
}
