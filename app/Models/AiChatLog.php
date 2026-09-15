<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiChatLog extends Model
{
    protected $table = 'ai_chat_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_name',
        'question',
        'response',
        'executed_sql',
        'was_routed',
    ];

    protected $casts = [
        'was_routed' => 'boolean',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }
}
