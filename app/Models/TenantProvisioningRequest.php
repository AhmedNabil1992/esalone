<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantProvisioningRequest extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'retryable' => 'boolean',
            'completed_at' => 'immutable_datetime',
        ];
    }
}
