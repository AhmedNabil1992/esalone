<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpenseHistory extends Model
{
    use BelongsToBranch;

    protected $table = 'expense_histories';

    protected $fillable = [
        'branch_id',
        'shift_id',
        'cash_box_id',
        'created_by_user_id',
        'expenses_id',
        'reference_type',
        'reference_id',
        'direction',
        'category',
        'amount',
        'affects_profit',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'affects_profit' => 'boolean',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $history): void {
            if (! $history->shift_id) {
                $history->shift_id = Shift::currentOpenShiftId()
                    ?? Shift::query()->orderByDesc('id')->value('id');
            }
        });
    }

    public function cashBox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class, 'cash_box_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpensesType::class, 'expenses_id');
    }
}
