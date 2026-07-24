<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\SystemConfig as Setting;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

class CurrentCashBox extends StatsOverviewWidget
{
    use HasWidgetShield;

    protected static ?int $sort = 1;

    protected ?string $heading = 'رصيد الخزن الحالية';

    protected function getStats(): array
    {
        $stats = [];
        $currencyCode = Setting::get('sys_locale_currency', 'EGP');
        $cashBoxes = CashBox::where('is_active', true)->get();

        foreach ($cashBoxes as $cashBox) {
            $formattedBalance = Number::currency($cashBox->current_balance, $currencyCode, 'ar');

            $stats[] = Stat::make($cashBox->name, $formattedBalance)
                ->description('الرصيد الحالي للصندوق')
                ->icon('heroicon-o-currency-dollar')
                ->color('success');
        }

        return $stats;
    }
}
