<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitingList extends Model
{
    protected $table = 'waiting_lists';

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'devices_type_id',
        'status',
        'notes',
    ];

    public function devicesType(): BelongsTo
    {
        return $this->belongsTo(DevicesType::class, 'devices_type_id');
    }
}
