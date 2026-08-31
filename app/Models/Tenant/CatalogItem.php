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
        'direct_sale_price',
        'device_sale_price',
        'table_sale_price',
        'is_product',
        'is_component',
        'is_active',
        'min_quantity',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'direct_sale_price' => 'decimal:2',
        'device_sale_price' => 'decimal:2',
        'table_sale_price' => 'decimal:2',
        'is_product' => 'boolean',
        'is_component' => 'boolean',
        'is_active' => 'boolean',
        'min_quantity' => 'decimal:3',
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

    public function branchPrices(): HasMany
    {
        return $this->hasMany(CatalogItemBranchPrice::class, 'catalog_item_id');
    }

    public function getEffectiveSalePrice(?int $branchId = null, string $priceType = 'direct_sale'): float
    {
        $targetBranchId = $branchId ?? (session('active_branch_id') !== 'all' && session('active_branch_id') !== null ? (int) session('active_branch_id') : null);

        $priceColumn = match ($priceType) {
            'device_sale', 'device', 'room' => 'device_sale_price',
            'table_sale', 'table' => 'table_sale_price',
            default => 'direct_sale_price',
        };

        if ($targetBranchId) {
            $branchPriceRecord = $this->branchPrices()
                ->where('branch_id', $targetBranchId)
                ->first();

            if ($branchPriceRecord) {
                if (isset($branchPriceRecord->is_available) && ! $branchPriceRecord->is_available) {
                    return 0.0;
                }

                $branchPrice = $branchPriceRecord->{$priceColumn} ?? $branchPriceRecord->sale_price;
                if ($branchPrice !== null && (float) $branchPrice > 0) {
                    return (float) $branchPrice;
                }
            }
        }

        $defaultPrice = $this->{$priceColumn} ?? $this->sale_price;

        return (float) ($defaultPrice ?: $this->sale_price);
    }
}
