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
}
