<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\ExpensesType;
use App\Models\Tenant\SystemConfig as Setting;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpensesTypeSum extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'إجمالي مصاريف الشهر الحالي حسب النوع';

    protected function getStats(): array
    {
        // تحديد بداية الشهر وحتى الوقت الحالي (من أول يوم في الشهر لحد اليوم)
        $startOfMonth = Carbon::now()->startOfMonth();
        $now = Carbon::now();

        // سحب رمز العملة الحالي للصالة (مثلاً EGP) لتنسيق المظهر المالي
        $currencyCode = (string) (Setting::get('sys_locale_currency') ?: 'EGP');

        $expenseHistoriesTable = (new ExpenseHistory)->getTable();

        // التجميع الفعلي يتم من جدول ExpenseHistory لأنه مصدر بيانات المصروفات
        $totalsByExpenseType = DB::table($expenseHistoriesTable)
            ->where('occurred_at', '>=', $startOfMonth)
            ->where('occurred_at', '<=', $now)
            ->where('direction', 'out')
            ->whereNotNull('expenses_id')
            ->selectRaw('expenses_id, SUM(amount) as total_amount')
            ->groupBy('expenses_id')
            ->pluck('total_amount', 'expenses_id');

        // جدول ExpensesType فقط لجلب اسم كل بند وربطه بقيمته المجمعة
        $expensesTypes = ExpensesType::query()->get();

        return $expensesTypes->map(function ($type) use ($currencyCode, $totalsByExpenseType) {
            // لو النوع ملوش مصروفات الشهر ده، بنخلي القيمة الافتراضية صفر
            $sumAmount = $totalsByExpenseType->get($type->id, 0);

            // تنسيق الرقم ماليًا ليظهر بشكل احترافي (مثل: 1,250.00 ج.م) بناءً على عملة العميل
            $formattedAmount = Number::currency($sumAmount, $currencyCode, 'ar');

            return Stat::make(
                label: $type->name,
                value: $formattedAmount
            );
            // ->description('إجمالي مصروفات هذا الشهر')
            // ->descriptionIcon('heroicon-m-arrow-trending-down')
            // ->color('danger') // اللون الأحمر ليدل على الخصم والمصروفات
        })->toArray();
    }
}
