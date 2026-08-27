<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItemBranchPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'catalog_item_id',
        'branch_id',
        'sale_price',
        'direct_sale_price',
        'device_sale_price',
        'table_sale_price',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'direct_sale_price' => 'decimal:2',
            'device_sale_price' => 'decimal:2',
            'table_sale_price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
