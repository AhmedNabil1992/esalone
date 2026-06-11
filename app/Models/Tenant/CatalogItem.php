<?php

namespace App\Models\Tenant;

use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_group_id',
        'name',
        'description',
        'picture',
        'purchase_price',
        'sale_price',
        'is_product',
        'is_component',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_product' => 'boolean',
        'is_component' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function newFactory(): CatalogItemFactory
    {
        return CatalogItemFactory::new();
    }

    public function inventoryGroup(): BelongsTo
    {
        return $this->belongsTo(InventoryGroup::class, 'inventory_group_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'product_id');
    }

    public function usedInProducts(): HasMany
    {
        return $this->hasMany(ProductComponent::class, 'component_id');
    }
}
