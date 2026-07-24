<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class SalesChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'منحنى المبيعات والإيرادات اليومية (آخر 30 يوم)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $rawSales = Invoice::query()
            ->where('status', 'posted')
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->selectRaw('DATE(issued_at) as date, SUM(final_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dateString = $date->toDateString();
            $labels[] = $date->format('m-d');
            $data[] = (float) ($rawSales[$dateString] ?? 0.0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'إجمالي الإيرادات (ج.م)',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
