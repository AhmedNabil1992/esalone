<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiTrainingRule extends Model
{
    protected $table = 'ai_training_rules';

    protected $fillable = [
        'pattern',
        'sql_query',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection');
    }
}
