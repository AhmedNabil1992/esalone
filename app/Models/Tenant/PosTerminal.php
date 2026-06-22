<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class PosTerminal extends Model
{
    protected $fillable = [
        'name',
        'code',
        'serial_number',
        'is_active',
        'is_approved',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
    ];
}
