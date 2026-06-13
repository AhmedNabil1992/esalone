<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * دالة سريعة لجلب قيمة أي إعداد
     * مثال: Setting::get('tax_value', 14);
     */
    public static function get(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * دالة سريعة لحفظ أو تحديث أي إعداد
     * مثال: Setting::set('tax_value', 14);
     */
    public static function set(string $key, $value, string $group = 'general')
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
    }
}
