<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'shift_no',
        'shift_date',
        'shift_from',
        'shift_to',
        'opened_by_user_id',
        'closed_by_user_id',
        'start_amount',
        'playstation_amount',
        'sales_amount',
        'customer_add_amount',
        'income_history_amount',
        'tax_amount',
        'service_amount',
        'purchase_amount',
        'expenses_amount',
        'discount_amount',
        'customer_minus_amount',
        'customer_credit_amount',
        'credit_amount',
        'actual_amount',
        'is_active',
        'status',
        'notify',
        'notes',
        'cashbox_actual_amounts',
        'expected_amount',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_from' => 'datetime',
        'shift_to' => 'datetime',
        'start_amount' => 'decimal:2',
        'playstation_amount' => 'decimal:2',
        'sales_amount' => 'decimal:2',
        'customer_add_amount' => 'decimal:2',
        'income_history_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'service_amount' => 'decimal:2',
        'purchase_amount' => 'decimal:2',
        'expenses_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'customer_minus_amount' => 'decimal:2',
        'customer_credit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'notify' => 'boolean',
        'cashbox_actual_amounts' => 'array',
    ];

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'shift_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function nextShiftNumber(?int $branchId = null): int
    {
        $query = static::query();

        $resolvedBranchId = $branchId
            ?? (session('active_branch_id') && session('active_branch_id') !== 'all' ? (int) session('active_branch_id') : null)
            ?? (Auth::user()?->branches()->first()?->id ?? Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1);

        if ($resolvedBranchId) {
            $query->where('branch_id', (int) $resolvedBranchId);
        }

        return (int) ($query->max('shift_no') ?? 0) + 1;
    }

    public static function currentOpenShift(?int $branchId = null): ?self
    {
        $query = static::query()
            ->open()
            ->active();

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif (session('active_branch_id') && session('active_branch_id') !== 'all') {
            $query->where('branch_id', (int) session('active_branch_id'));
        }

        return $query->orderByDesc('id')->first();
    }

    public static function currentOpenShiftId(?int $branchId = null): ?int
    {
        return static::currentOpenShift($branchId)?->id;
    }

    public static function currentOpenShiftNumber(?int $branchId = null): ?int
    {
        return static::currentOpenShift($branchId)?->shift_no;
    }

    public function getRemainAmountAttribute(): float
    {
        return round(
            (float) $this->start_amount
            + (float) $this->playstation_amount
            + (float) $this->sales_amount
            + (float) $this->customer_add_amount
            + (float) $this->income_history_amount
            + (float) $this->tax_amount
            + (float) $this->service_amount
            - (float) $this->purchase_amount
            - (float) $this->expenses_amount
            - (float) $this->discount_amount
            - (float) $this->customer_credit_amount
            - (float) $this->customer_minus_amount
            + (float) $this->credit_amount,
            2,
        );
    }

    public function getDifferenceAmountAttribute(): float
    {
        return round($this->remain_amount - (float) $this->actual_amount, 2);
    }
}
