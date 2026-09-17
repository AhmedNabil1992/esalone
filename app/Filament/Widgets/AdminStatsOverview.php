<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Customer;
use App\Models\Tenant\Devices;
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

    protected ?string $heading = 'ملخص إحصائيات وعمليات النظام';

    protected function getStats(): array
    {
        $currencyCode = Setting::get('sys_locale_currency', 'EGP');
        [$todayStart, $todayEnd] = app(TenantClock::class)->localDateRangeUtc(
            app(TenantClock::class)->nowLocal(),
        );

        // 1. Devices Activity Stat
        $totalDevices = Devices::count();
        $busyDevices = Devices::where('status', 'busy')->count();
        $devicesText = "{$busyDevices} من {$totalDevices} نشطة حالياً";

        // 2. Customers Stat
        $totalCustomers = Customer::count();

        // 3. Current Open Shift Stat
        $openShift = Shift::currentOpenShift();
        $shiftNoText = $openShift ? "الشيفت النشط رقم #{$openShift->shift_no}" : 'لا يوجد شيفت مفتوح';

        // 4. Today's Financials
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
            Stat::make('نشاط الأجهزة والرموز', $devicesText)
                ->description('نسبة إشغال أجهزة الصالة بالوقت الحالي')
                ->icon('heroicon-o-tv')
                ->color($busyDevices > 0 ? 'success' : 'gray'),

            Stat::make('الوردية الحالية', $shiftNoText)
                ->description($openShift ? 'الوردية مفتوحة ويتم تسجيل العمليات عليها' : 'يرجى فتح شيفت لبدء العمل')
                ->icon('heroicon-o-clock')
                ->color($openShift ? 'success' : 'danger'),

            Stat::make('العملاء المشتركين', number_format($totalCustomers))
                ->description('إجمالي حسابات العملاء المسجلة بالسيستم')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('إجمالي إيرادات اليوم', Number::currency($todayInvoicesRevenue, $currencyCode, 'ar'))
                ->description('إجمالي مبيعات المتجر وإيرادات اللعب اليوم')
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
