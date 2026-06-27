<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlayHistory extends Model
{
    protected $table = 'play_history';

    protected $fillable = [
        'device_id',
        'play_type',
        'hour_price',
        'start_time',
        'end_time',
        'actual_end_time',
        'play_time',
        'cost',
        'username',
        'shift_no',
        'invoice_no',
        'trx_date',
    ];

    protected $casts = [
        'device_id' => 'integer',
        'hour_price' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'play_time' => 'integer',
        'cost' => 'decimal:2',
        'shift_no' => 'integer',
        'trx_date' => 'date',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class, 'device_id');
    }

    public function getPlayPriceAttribute(): mixed
    {
        return $this->hour_price;
    }

    public function getPeriodAttribute(): ?int
    {
        return $this->play_time;
    }

    public function getPlayCategoryAttribute(): ?string
    {
        return null;
    }
}
