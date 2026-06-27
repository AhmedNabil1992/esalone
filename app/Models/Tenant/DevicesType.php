<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevicesType extends Model
{
    use HasFactory;

    /**
     * الحقول المسموح بتعبئتها دفعة واحدة (Mass Assignment).
     */
    protected $fillable = [
        'name',
        'description',
        'picture',
        'is_active',
        'fixed_times',
    ];

    /**
     * تحويل أنواع البيانات تلقائياً (Casting).
     * بنستخدمها هنا عشان حقل is_active يرجعلك دايماً true أو false كـ Boolean صريح مش 1 أو 0.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'fixed_times' => 'array',
    ];

    /**
     * جلب جميع الأجهزة التابعة لهذا النوع.
     */
    public function devices(): HasMany
    {
        return $this->hasMany(Devices::class, 'devices_type_id');
    }
}
