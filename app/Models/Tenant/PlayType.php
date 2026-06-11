<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PlayType extends Model
{
    protected $fillable = [
        'device_type_id',
        'name',
        'description',
        'picture',
    ];

    public function deviceType()
    {
        return $this->belongsTo(DevicesType::class, 'device_type_id');
    }

    public function playPrices()
    {
        return $this->hasMany(PlayPrice::class, 'play_type_id');
    }
}
