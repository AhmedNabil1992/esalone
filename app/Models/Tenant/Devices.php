<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devices extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بتعبئتها دفعة واحدة (Mass Assignment).
     */
    protected $fillable = [
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

    public function playTypes()
    {
        return $this->hasMany(PlayType::class, 'device_id');
    }

    public function playPrices()
    {
        return $this->hasMany(PlayPrice::class, 'device_id');
    }
}
