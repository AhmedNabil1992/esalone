<?php

namespace App\Filament\Pages;

use App\Models\Tenant\Customer;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;

class CustomerAnalyticsReport extends Page
{
    use HasPageShield;

    protected static ?string $title = 'تقرير ولاء وتحليل العملاء';

    protected static ?string $navigationLabel = 'تحليل وولاء العملاء';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'تقارير إدارية';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.customer-analytics-report';

    /**
     * @return array<int, array{id: int, name: string, phone: string, total_spent: float, level_name: string|null}>
     */
    public function getTopSpenders(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->withSum(['invoices' => fn ($q) => $q->where('status', 'posted')], 'final_amount')
            ->with('level')
            ->orderByDesc('invoices_sum_final_amount')
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'phone' => (string) ($c->phone ?? '—'),
                'total_spent' => (float) ($c->invoices_sum_final_amount ?? 0.0),
                'level_name' => $c->level?->name,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string, visits_count: int, level_name: string|null}>
     */
    public function getMostFrequent(): array
    {
        return Customer::query()
            ->where('is_active', true)
            ->withCount(['invoices' => fn ($q) => $q->where('status', 'posted')])
            ->with('level')
            ->orderByDesc('invoices_count')
            ->limit(15)
            ->get()
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'name' => (string) $c->name,
                'phone' => (string) ($c->phone ?? '—'),
                'visits_count' => (int) ($c->invoices_count ?? 0),
                'level_name' => $c->level?->name,
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{id: int, name: string, phone: string, last_visit: string|null, days_since_last_visit: int, level_name: string|null}>
     */
    public function getChurnRiskCustomers(): array
    {
        $cutoffDate = Carbon::now()->subDays(14);

        return Customer::query()
            ->where('is_active', true)
            // Has not visited in the last 14 days
            ->whereDoesntHave('invoices', function ($q) use ($cutoffDate) {
                $q->where('status', 'posted')
                    ->where('issued_at', '>=', $cutoffDate);
            })
            // But has visited historically at least once
            ->whereHas('invoices', function ($q) {
                $q->where('status', 'posted');
            })
            ->withMax(['invoices' => fn ($q) => $q->where('status', 'posted')], 'issued_at')
            ->with('level')
            ->orderBy('invoices_max_issued_at', 'asc') // Oldest visits first
            ->limit(15)
            ->get()
            ->map(function ($c) {
                $lastVisit = $c->invoices_max_issued_at ? Carbon::parse($c->invoices_max_issued_at) : null;
                $days = $lastVisit ? (int) Carbon::now()->diffInDays($lastVisit) : 0;

                return [
                    'id' => (int) $c->id,
                    'name' => (string) $c->name,
                    'phone' => (string) ($c->phone ?? '—'),
                    'last_visit' => $lastVisit ? $lastVisit->toDateString() : '—',
                    'days_since_last_visit' => $days,
                    'level_name' => $c->level?->name,
                ];
            })
            ->toArray();
    }
}
