<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Tenant\Purchase;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'ملخص المشتريات';

    protected function getStats(): array
    {
        $now = CarbonImmutable::now();
        $monthStart = $now->startOfMonth();
        $monthEnd = $now->endOfMonth();

        return [
            Stat::make('عدد فواتير المشتريات', Purchase::query()->get()->count())
                ->description('إجمالي الفواتير المسجلة')
                ->icon('heroicon-o-document-text')
                ->color('primary'),
            Stat::make('إجمالي هذا الشهر', Purchase::query()
                ->where('purchased_at', '>=', $monthStart)
                ->where('purchased_at', '<=', $monthEnd)
                ->sum('total_amount'))
                ->description('قيمة المشتريات خلال الشهر الحالي')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
            Stat::make('الفواتير المعكوسة', Purchase::query()
                ->where('status', 'reversed')
                ->get()->count())
                ->description('فواتير تم عكسها')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger'),
        ];
    }
}
