<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PlayPrice extends Model
{
    protected $fillable = [
        'play_type_id',
        'device_id',
        'description',
        'price',
        'start_from',
    ];

    public function playType()
    {
        return $this->belongsTo(PlayType::class, 'play_type_id');
    }

    public function device()
    {
        return $this->belongsTo(Devices::class, 'device_id');
    }

    public function deviceType()
    {
        return $this->belongsTo(DevicesType::class, 'device_type_id');
    }
}
