<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerLevel extends Model
{
    protected $table = 'customer_levels';

    protected $fillable = [
        'name',
        'points_required',
        'discount_percent',
        'applies_to',
        'is_active',
    ];

    protected $casts = [
        'points_required' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'customer_level_id');
    }
}
