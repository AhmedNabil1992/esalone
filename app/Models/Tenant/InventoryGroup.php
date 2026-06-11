<?php

namespace App\Models\Tenant;

use Database\Factories\InventoryGroupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'picture',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): InventoryGroupFactory
    {
        return InventoryGroupFactory::new();
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class, 'inventory_group_id');
    }
}
