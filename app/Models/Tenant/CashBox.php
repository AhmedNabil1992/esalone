<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBox extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'type',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CashBox $cashBox): void {
            if (blank($cashBox->code)) {
                $maxCode = static::withoutGlobalScopes()->max('code');
                $cashBox->code = (string) (((int) $maxCode) + 1);
            }
        });
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'cash_box_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'cash_box_id');
    }

    public function expenseHistories(): HasMany
    {
        return $this->hasMany(ExpenseHistory::class, 'cash_box_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(CashBoxLedgerEntry::class, 'cash_box_id');
    }
}
