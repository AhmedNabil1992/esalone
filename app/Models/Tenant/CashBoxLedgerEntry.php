<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashBoxLedgerEntry extends Model
{
    use BelongsToBranch;

    protected $fillable = [
        'branch_id',
        'shift_id',
        'cash_box_id',
        'created_by_user_id',
        'reference_type',
        'reference_id',
        'direction',
        'operation',
        'amount',
        'description',
        'occurred_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry): void {
            if (! $entry->shift_id) {
                $entry->shift_id = Shift::currentOpenShiftId()
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
}
