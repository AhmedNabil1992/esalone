<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\Storage;
use Swis\Filament\Backgrounds\Contracts\ProvidesImages;
use Swis\Filament\Backgrounds\Image;
use Throwable;

class TenantBackgroundsProvider implements ProvidesImages
{
    public static function make(): static
    {
        return new static;
    }

    public function getImage(): Image
    {
        // 1. مسار الصورة الاحتياطية (لو العميل معندوش صور أو فولدره ممسوح)
        // تأكد إنك حاطط صورة اسمها default.png جوه مجلد public/images/backgrounds/
        $defaultUrl = asset('images/backgrounds/default.png');

        // 2. التأكد إن فيه عميل فعلياً فاتح السيستم
        if (! tenant('id')) {
            return new Image($defaultUrl);
        }

        try {
            // 3. قراءة الملفات من الديسك المربوط بالعميل أوتوماتيك
            $files = Storage::disk('public')->files('backgrounds');

            if (empty($files)) {
                return new Image($defaultUrl);
            }

            // 4. اختيار صورة عشوائية
            $randomFile = $files[array_rand($files)];

            // السحر هنا: استخدام tenant_asset لضمان الدخول على الـ Storage الصحيح
            $url = tenant_asset($randomFile);

            // معالجة المسافات في اسم الملف عشان متكسرش الـ CSS
            $url = str_replace(' ', '%20', $url);

            // إرجاع الرابط مباشرة للكلاس
            return new Image($url);

        } catch (Throwable $e) {
            // لو حصل أي خطأ، نرجع للاحتياطي
            return new Image($defaultUrl);
        }
    }
}
