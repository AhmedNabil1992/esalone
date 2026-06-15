<?php

namespace App\Filament\Customer\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CustomerBalance extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected function getStats(): array
    {
        $user = Auth::user(); // Get the currently authenticated user
        return [
            Stat::make('رصيدك الحالي', $user->balance ?? 0)
                // ->description('الرصيد المتاح في حسابك')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success')
                ->icon('heroicon-o-currency-dollar')
                ,
        ];
    }
}
