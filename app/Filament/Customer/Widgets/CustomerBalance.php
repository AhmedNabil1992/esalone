<?php

declare(strict_types=1);

namespace App\Filament\Customer\Widgets;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Reservation;
use App\Scopes\BranchScope;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CustomerBalance extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = Auth::guard('customer')->user();
        $customerId = $user?->id;

        $invoicesCount = $customerId
            ? Invoice::withoutGlobalScope(BranchScope::class)->where('customer_id', $customerId)->count()
            : 0;

        $activeReservationsCount = $customerId
            ? Reservation::withoutGlobalScope(BranchScope::class)->where('customer_id', $customerId)->where('status', 'active')->count()
            : 0;

        return [
            Stat::make('رصيدك الحالي', number_format((float) ($user->balance ?? 0), 2).' ج.م')
                ->description('الرصيد المتاح لحسابك')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),
            Stat::make('نقاط الولاء', number_format((float) ($user->points ?? 0), 0).' نقطة')
                ->description('المتاحة للتحويل')
                ->color('warning')
                ->icon('heroicon-o-star'),
            Stat::make('عدد الفواتير', (string) $invoicesCount)
                ->description('إجمالي عملياتك المسجلة')
                ->color('info')
                ->icon('heroicon-o-receipt-percent'),
            Stat::make('الحجوزات النشطة', (string) $activeReservationsCount)
                ->description('حجوزات الأجهزة القادمة')
                ->color('primary')
                ->icon('heroicon-o-calendar-days'),
        ];
    }
}
