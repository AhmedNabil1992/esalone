<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'cash_box_id',
        'supplier_id',
        'shift_id',
        'reference',
        'status',
        'notes',
        'total_amount',
        'purchased_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'purchased_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $purchase): void {
            if (! $purchase->shift_id) {
                $purchase->shift_id = Shift::currentOpenShiftId()
                    ?? Shift::query()->orderByDesc('id')->value('id');
            }
        });
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cash_box_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
