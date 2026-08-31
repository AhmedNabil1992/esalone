<?php

namespace App\Models\Tenant;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Devices extends Model
{
    use BelongsToBranch, HasFactory;

    /**
     * الحقول المسموح بتعبئتها دفعة واحدة (Mass Assignment).
     */
    protected $fillable = [
        'branch_id',
        'devices_type_id',
        'name',
        'ip_address',
        'mac_address',
        'limit_time',
        'is_time',
        'status',
        'is_active',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً (Casting).
     * بنستخدمها هنا عشان الحقول ترجعلك دايماً بالقيم الصحيحة.
     */
    protected $casts = [
        'is_time' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * علاقة الجهاز بنوع الجهاز.
     * كل جهاز ينتمي إلى نوع جهاز واحد محدد.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DevicesType::class, 'devices_type_id');
    }

    public function playTypes(): HasMany
    {
        return $this->hasMany(PlayType::class, 'device_type_id', 'devices_type_id');
    }

    public function playPrices(): HasMany
    {
        return $this->hasMany(PlayPrice::class, 'device_id');
    }

    public function activeSession(): HasOne
    {
        return $this->hasOne(PlaySessions::class, 'device_id')
            ->where('status', 'active')
            ->latestOfMany();
    }
}
