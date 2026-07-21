<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickNavigationFavorites extends Model
{
    protected $table = 'quick_navigation_favorites';

    protected $fillable = [
        'user_id',
        'label',
        'url',
        'sort',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
