<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiIdempotencyKey extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'response_status' => 'integer',
            'completed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
