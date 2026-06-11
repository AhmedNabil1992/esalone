<?php

namespace App\Models\Tenant;

use Database\Factories\ProductComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'component_id',
        'quantity',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'sort_order' => 'integer',
    ];

    protected static function newFactory(): ProductComponentFactory
    {
        return ProductComponentFactory::new();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'product_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'component_id');
    }
}
