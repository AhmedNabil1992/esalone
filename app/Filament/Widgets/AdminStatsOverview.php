<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Customer;
use App\Models\Tenant\ExpenseHistory;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Purchase;
use App\Models\Tenant\Shift;
use App\Models\Tenant\SystemConfig as Setting;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class AdminStatsOverview extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 0;

    protected ?string $heading = 'ملخص أداء الصالون والفرع';

    protected function getStats(): array
    {
        $currencyCode = Setting::get('sys_locale_currency', 'EGP');
        [$todayStart, $todayEnd] = app(TenantClock::class)->localDateRangeUtc(
            app(TenantClock::class)->nowLocal(),
        );

        $todayAppointments = Appointment::query()
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->whereBetween('start_at', [$todayStart, $todayEnd])
            ->count();

        $totalCustomers = Customer::count();

        $openShift = Shift::currentOpenShift();
        $shiftNoText = $openShift ? "الشيفت النشط رقم #{$openShift->shift_no}" : 'لا يوجد شيفت مفتوح';

        $todayInvoicesRevenue = (float) Invoice::query()
            ->where('status', 'posted')
            ->whereBetween('issued_at', [$todayStart, $todayEnd])
            ->sum('final_amount');

        $todayExpenses = (float) ExpenseHistory::query()
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('amount');

        $todayPurchases = (float) Purchase::query()
            ->where('status', 'posted')
            ->whereBetween('purchased_at', [$todayStart, $todayEnd])
            ->sum('total_amount');

        $netProfit = $todayInvoicesRevenue - $todayExpenses - $todayPurchases;

        return [
            Stat::make('مواعيد اليوم', number_format($todayAppointments))
                ->description('مواعيد مجدولة أو مؤكدة أو قيد التنفيذ')
                ->icon('heroicon-o-scissors')
                ->color($todayAppointments > 0 ? 'success' : 'gray'),

            Stat::make('الوردية الحالية', $shiftNoText)
                ->description($openShift ? 'الوردية مفتوحة ويتم تسجيل العمليات عليها' : 'يرجى فتح شيفت لبدء العمل')
                ->icon('heroicon-o-clock')
                ->color($openShift ? 'success' : 'danger'),

            Stat::make('العملاء المسجلون', number_format($totalCustomers))
                ->description('إجمالي العملاء المسجلين في الصالون')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('إجمالي إيرادات اليوم', Number::currency($todayInvoicesRevenue, $currencyCode, 'ar'))
                ->description('إيرادات الخدمات ومبيعات المنتجات اليوم')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('مصروفات ومشتريات اليوم', Number::currency($todayExpenses + $todayPurchases, $currencyCode, 'ar'))
                ->description('مصروفات تشغيلية ومشتريات بضاعة اليوم')
                ->icon('heroicon-o-arrow-trending-down')
                ->color('danger'),

            Stat::make('صافي السيولة النقدية لليوم', Number::currency($netProfit, $currencyCode, 'ar'))
                ->description('صافي السيولة الناتجة من معاملات اليوم')
                ->icon('heroicon-o-banknotes')
                ->color($netProfit >= 0 ? 'success' : 'danger'),
        ];
    }
}
