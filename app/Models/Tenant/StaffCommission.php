<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffCommission extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'staff_member_id',
        'appointment_id',
        'invoice_id',
        'commission_rate',
        'commission_base',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_base' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<StaffMember, $this> */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    /** @return BelongsTo<Appointment, $this> */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /** @return BelongsTo<Invoice, $this> */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /** @return HasMany<StaffCommissionPayout, $this> */
    public function payouts(): HasMany
    {
        return $this->hasMany(StaffCommissionPayout::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        $paidAmount = $this->getAttribute('payouts_sum_amount')
            ?? $this->payouts()->sum('amount');

        return round(max(0, (float) $this->amount - (float) $paidAmount), 2);
    }
}
