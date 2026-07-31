<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaySessionDetail extends Model
{
    use HasFactory;

    protected $table = 'play_session_details';

    protected $fillable = [
        'play_session_id',
        'device_id',
        'play_type',
        'play_category',
        'play_price',
        'start_time',
        'end_time',
        'period',
        'cost',
        'status_img',
        'status',
        'order_no',
        'kind',
        'user_id',
        'shift_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * الحركة تنتمي إلى جلسة لعب أم معينة.
     */
    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySessions::class, 'play_session_id');
    }

    /**
     * الحركة تمت على جهاز معين.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class);
    }

    /**
     * الحركة قام بها كاشير معين.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * الحركة تمت في وردية معينة.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
