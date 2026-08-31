<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class PlaySessions extends Model
{
    use BelongsToBranch, HasFactory;

    protected $table = 'play_sessions';

    protected $fillable = [
        'branch_id',
        'device_id',
        'customer_id',
        'prepaid_amount',
        'session_key',
        'status',
        'alert_5min_sent',
        'alert_ended_sent',
    ];

    protected $casts = [
        'alert_5min_sent' => 'boolean',
        'alert_ended_sent' => 'boolean',
    ];

    /**
     * دالة الـ Boot لتنفيذ أكواد تلقائية عند حدوث عمليات على الموديل.
     */
    protected static function booted()
    {
        // الحدث ده بيتنفذ تلقائياً "قبل" ما السطر يتسيف في الـ Database لأول مرة
        static::creating(function ($playSession) {

            // بنعمل Loop للتأكد بنسبة 100% إن الكود المولّد فريد ومش موجود في الجدول قبل كده
            do {
                // توليد كود عشوائي مكون من 6 حروف وأرقام كبيرة (مثل: X7R29B)
                $key = strtoupper(Str::random(6));
            } while (static::where('session_key', $key)->exists());

            // تعيين الكود للحقل تلقائياً
            $playSession->session_key = $key;
        });
    }

    /**
     * الجلسة تنتمي إلى جهاز رئيسي.
     */
    public function device(): BelongsTo
    {
        return $this->belongsTo(Devices::class);
    }

    /**
     * الجلسة الأم تحتوي على تفاصيل حركات كثيرة (تنقلات، تغيير نوع اللعب)
     * مربوطة بجدول التفاصيل (الوريث لـ play_wait)
     */
    public function details(): HasMany
    {
        return $this->hasMany(PlaySessionDetail::class, 'play_session_id');
    }

    public function latestDetail(): HasOne
    {
        return $this->hasOne(PlaySessionDetail::class, 'play_session_id')->latestOfMany();
    }

    public function orderSessionDetails(): HasMany
    {
        return $this->hasMany(OrderSessionDetail::class, 'play_session_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function getOriginalStartTime(): ?Carbon
    {
        $first = $this->relationLoaded('details')
            ? $this->details->sortBy('id')->firstWhere('start_time', '!==', null)
            : $this->details()->whereNotNull('start_time')->orderBy('id', 'asc')->first();

        return $first && $first->start_time ? \Illuminate\Support\Carbon::parse($first->start_time) : null;
    }

    public function getTotalPeriod(): int
    {
        return (int) ($this->relationLoaded('details')
            ? $this->details->sum('period')
            : $this->details()->sum('period'));
    }
}
