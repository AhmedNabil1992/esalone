<?php

namespace App\Filament\Widgets;

use App\Models\Tenant\Invoice;
use App\Support\Tenant\TenantClock;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;

class SalesChartWidget extends ChartWidget
{
    use HasWidgetShield;

    protected ?string $heading = 'منحنى المبيعات والإيرادات اليومية (آخر 30 يوم)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $clock = app(TenantClock::class);
        $localToday = $clock->nowLocal();
        $startDate = $clock->localDateStartUtc($localToday->subDays(29));
        $endDate = $clock->localDateEndUtc($localToday);

        $rawSales = Invoice::query()
            ->where('status', 'posted')
            ->whereBetween('issued_at', [$startDate, $endDate])
            ->get(['issued_at', 'final_amount'])
            ->groupBy(fn (Invoice $invoice): string => $clock->utcToLocal($invoice->issued_at)->toDateString())
            ->map(fn ($invoices): float => (float) $invoices->sum('final_amount'));

        $data = [];
        $labels = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = $localToday->subDays($i);
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
