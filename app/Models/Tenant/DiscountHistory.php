<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountHistory extends Model
{
    use BelongsToBranch;

    protected $table = 'discount_histories';

    protected $fillable = [
        'branch_id',
        'invoice_id',
        'invoice_no',
        'amount',
        'reason',
        'shift_id',
        'user_id',
        'ledger_entry_id',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(CashBoxLedgerEntry::class, 'ledger_entry_id');
    }
}
