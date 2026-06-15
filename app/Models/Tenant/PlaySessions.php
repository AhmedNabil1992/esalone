<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PlaySessions extends Model
{
    use HasFactory;

    protected $table = 'play_sessions';

    protected $fillable = [
        'device_id',
        'session_key',
        'status',
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
        // لاراڤيل لوحده هيتوقع إن الفوريّن كي اسمه play_session_id جوه جدول play_session_details
        return $this->hasMany(PlaySessionDetail::class);
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
