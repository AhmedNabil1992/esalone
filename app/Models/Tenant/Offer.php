<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'name',
        'devices',
        'time_from',
        'time_to',
        'period',
        'play_type',
        'price',
        'remark',
        'is_active',
    ];

    protected $casts = [
        'period' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
