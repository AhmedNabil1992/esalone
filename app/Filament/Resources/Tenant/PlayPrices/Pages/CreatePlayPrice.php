<?php

namespace App\Filament\Resources\Tenant\PlayPrices\Pages;

use App\Filament\Resources\Tenant\PlayPrices\PlayPriceResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePlayPrice extends CreateRecord
{
    protected static string $resource = PlayPriceResource::class;

    /**
     * التحكم المخصص في عملية إنشاء الأسطر في قاعدة البيانات
     */
    protected function handleRecordCreation(array $data): Model
    {
        $insertedRecord = null;

        // 1. نسحب مصفوفة الأجهزة اللي يوزر اختارها من الفورم
        $deviceIds = $data['device_ids'] ?? [];

        // 2. نلف على جهاز جهاز ونعمل له سيف في الداتا بيز
        foreach ($deviceIds as $deviceId) {
            
            // بنجهز البيانات للسطر الحالي وبنربطه بالـ device_id المفرد
            $recordData = $data;
            unset($recordData['device_ids']); // بنشيل المصفوفة عشان الميجريشن ميزعلش
            $recordData['device_id'] = $deviceId; // بنحط الـ ID المفرد للجدول

            // التكريت الفعلي للسطر الحالي جوه جدول الأسعار
            $insertedRecord = static::getModel()::create($recordData);
        }

        // فيلامنت بيحتاج يرجع "موديل واحد" عشان يوديه لصفحة الـ view أو الـ index بعد التقفيل
        // هنرجع له آخر سطر تم إنشاؤه وهو هيقوم بالواجب
        return $insertedRecord;
    }

}
