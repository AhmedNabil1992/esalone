<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCommissionPayout extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'staff_commission_id',
        'expense_history_id',
        'created_by_user_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<StaffCommission, $this> */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(StaffCommission::class, 'staff_commission_id');
    }

    /** @return BelongsTo<ExpenseHistory, $this> */
    public function expenseHistory(): BelongsTo
    {
        return $this->belongsTo(ExpenseHistory::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
