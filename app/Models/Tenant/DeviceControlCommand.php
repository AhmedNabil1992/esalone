<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class DeviceControlCommand extends Model
{
    protected $fillable = [
        'device_id',
        'ip_address',
        'device_name',
        'command',
        'action',
        'username',
        'remark',
        'status',
        'processed_by',
        'retry_count',
        'sent_at',
        'ack_at',
        'error_message',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'ack_at' => 'datetime',
    ];
}
