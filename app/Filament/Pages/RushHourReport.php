<?php

namespace App\Filament\Pages;

use App\Models\Tenant\PlayHistory;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class RushHourReport extends Page
{
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    use HasPageShield;

    protected static ?string $title = 'تقرير أوقات الذروة والإشغال (Rush Hour)';

    protected static ?string $navigationLabel = 'تحليل أوقات الذروة (Rush Hour)';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'الإحصائيات';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.rush-hour-report';

    public ?string $from = null;

    public ?string $until = null;

    public string $hourlyDataSerialized = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::today()->toDateString();
        $this->updateSerializedData();
    }

    public function updated(string $propertyName): void
    {
        if (! in_array($propertyName, ['from', 'until'], true)) {
            return;
        }

        $this->updateSerializedData();
    }

    public function updateSerializedData(): void
    {
        $this->hourlyDataSerialized = json_encode($this->getHourlyStats());
    }

    /**
     * @return array<int, array{hour: int, label: string, sessions_count: int, total_revenue: float, total_play_time: int}>
     */
    public function getHourlyStats(): array
    {
        $hourlyData = PlayHistory::query()
            ->select(
                DB::raw('HOUR(start_time) as hour_of_day'),
                DB::raw('COUNT(*) as sessions_count'),
                DB::raw('SUM(cost) as total_revenue'),
                DB::raw('SUM(play_time) as total_play_time')
            )
            ->when($this->from, fn ($q) => $q->whereDate('trx_date', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('trx_date', '<=', $this->until))
            ->groupBy(DB::raw('HOUR(start_time)'))
            ->orderBy('hour_of_day', 'asc')
            ->get();

        $stats = [];
        for ($i = 0; $i < 24; $i++) {
            $stats[$i] = [
                'hour' => $i,
                'label' => $this->formatHourLabel($i),
                'sessions_count' => 0,
                'total_revenue' => 0.0,
                'total_play_time' => 0,
            ];
        }

        foreach ($hourlyData as $row) {
            if ($row->hour_of_day !== null) {
                $h = (int) $row->hour_of_day;
                $stats[$h]['sessions_count'] = (int) $row->sessions_count;
                $stats[$h]['total_revenue'] = (float) $row->total_revenue;
                $stats[$h]['total_play_time'] = (int) $row->total_play_time;
            }
        }

        return array_values($stats);
    }

    // Replaced by public property $hourlyDataSerialized

    public function getPeakHourProperty(): ?array
    {
        $stats = $this->getHourlyStats();
        $maxSessions = -1;
        $peak = null;

        foreach ($stats as $s) {
            if ($s['sessions_count'] > $maxSessions) {
                $maxSessions = $s['sessions_count'];
                $peak = $s;
            }
        }

        return ($maxSessions > 0) ? $peak : null;
    }

    public function getTotalSessionsProperty(): int
    {
        return (int) array_sum(array_column($this->getHourlyStats(), 'sessions_count'));
    }

    public function getTotalRevenueProperty(): float
    {
        return (float) array_sum(array_column($this->getHourlyStats(), 'total_revenue'));
    }

    private function formatHourLabel(int $hour): string
    {
        if ($hour === 0) {
            return '12 AM';
        }
        if ($hour === 12) {
            return '12 PM';
        }
        if ($hour > 12) {
            return ($hour - 12).' PM';
        }

        return $hour.' AM';
    }
}
