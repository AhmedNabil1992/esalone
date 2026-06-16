<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;
use App\Models\Tenant\ExpensesType;
use App\Models\Tenant\SystemConfig As Setting;
use Illuminate\Support\Number;
use App\Models\Tenant\FinancialTransaction;

class ExpensesTypeSum extends StatsOverviewWidget
{
    // protected static ?string $heading = 'إجمالي مصاريف الشهر الحالي حسب النوع';

    protected function getStats(): array
    {
        // تحديد بداية ونهاية الشهر الحالي بناءً على حقل التاريخ في جدولك
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // سحب رمز العملة الحالي للصالة (مثلاً EGP) لتنسيق المظهر المالي
        $currencyCode = Setting::get('sys_locale_currency', 'EGP');

        // جلب أنواع المصروفات مع حساب مجموع حقل amount من جدول الحركات المالي
        // اسم العلاقة هنا 'financialTransactions' وهي المعرفة في موديل ExpensesType بالأسفل
        $expensesTypes = ExpensesType::withSum(['financialTransactions' => function ($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('occurred_at', [$startOfMonth, $endOfMonth])
                  ->where('direction', 'out'); // فلترة إضافية لضمان أنها حركة خروج (مصروف)
        }], 'amount')->get();

        return $expensesTypes->map(function ($type) use ($currencyCode) {
            // لو النوع ملوش مصروفات الشهر ده، بنخلي القيمة الافتراضية صفر
            $sumAmount = $type->expenses_sum_amount ?? 0;

            // تنسيق الرقم ماليًا ليظهر بشكل احترافي (مثل: 1,250.00 ج.م) بناءً على عملة العميل
            $formattedAmount = Number::currency($sumAmount, $currencyCode, 'ar');

            return Stat::make(
                label: $type->name, 
                value: $formattedAmount
            )
            ->description('إجمالي مصروفات هذا الشهر')
            ->descriptionIcon('heroicon-m-arrow-trending-down')
            ->color('danger'); // اللون الأحمر ليدل على الخصم والمصروفات
        })->toArray();
    }
}
