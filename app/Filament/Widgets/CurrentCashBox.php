<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Tenant\CashBox;

class CurrentCashBox extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'رصيد الخزن الحالية';

    protected function getStats(): array
    {
        $stats = [];
        $cashBoxes = CashBox::where('is_active', true)->get();

        foreach ($cashBoxes as $cashBox) {
            $stats[] = Stat::make($cashBox->name, (string) $cashBox->current_balance)
                ->description('الرصيد الحالي للصندوق')
                ->icon('heroicon-o-currency-dollar')
                ->color('success');
        }

        return $stats;
    }
}
