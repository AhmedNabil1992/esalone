<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantWebhookDelivery extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'last_response_status' => 'integer',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
