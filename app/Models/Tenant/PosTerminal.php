<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;

class PosTerminal extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
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
