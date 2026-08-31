<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaitingList extends Model
{
    use BelongsToBranch;

    protected $table = 'waiting_lists';

    protected $fillable = [
        'branch_id',
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
