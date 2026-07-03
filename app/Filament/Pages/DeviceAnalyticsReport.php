<?php

namespace App\Filament\Pages;

use App\Models\Tenant\PlayHistory;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class DeviceAnalyticsReport extends Page
{
    protected static ?string $title = 'إحصائيات استخدام الأجهزة';

    protected static ?string $navigationLabel = 'إحصائيات الأجهزة';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tv';

    protected static string|\UnitEnum|null $navigationGroup = 'الإحصائيات';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.device-analytics-report';

    public ?string $from = null;

    public ?string $until = null;

    public string $chartDataSerialized = '';

    public function mount(): void
    {
        $this->from = Carbon::now()->startOfMonth()->toDateString();
        $this->until = Carbon::today()->toDateString();
        $this->updateChartData();
    }

    public function updated($propertyName): void
    {
        if (in_array($propertyName, ['from', 'until'])) {
            $this->updateChartData();
        }
    }

    public function updateChartData(): void
    {
        $stats = $this->getDeviceStats();
        $labels = [];
        $hours = [];
        $revenue = [];
        foreach ($stats as $s) {
            $labels[] = $s['device_name'];
            $hours[] = $s['total_hours'];
            $revenue[] = $s['total_revenue'];
        }
        $this->chartDataSerialized = json_encode([
            'labels' => $labels,
            'hours' => $hours,
            'revenue' => $revenue,
        ]);
    }

    /**
     * @return array<int, array{device_id: int, device_name: string, total_hours: float, total_sessions: int, total_revenue: float, play_types: array<string, int>}>
     */
    public function getDeviceStats(): array
    {
        $records = PlayHistory::query()
            ->join('devices', 'play_history.device_id', '=', 'devices.id')
            ->select(
                'play_history.device_id',
                'devices.name as device_name',
                'play_history.play_type',
                DB::raw('SUM(play_history.play_time) as total_minutes'),
                DB::raw('COUNT(*) as sessions_count'),
                DB::raw('SUM(play_history.cost) as total_revenue')
            )
            ->when($this->from, fn ($q) => $q->whereDate('play_history.trx_date', '>=', $this->from))
            ->when($this->until, fn ($q) => $q->whereDate('play_history.trx_date', '<=', $this->until))
            ->groupBy('play_history.device_id', 'devices.name', 'play_history.play_type')
            ->get();

        $devices = [];
        foreach ($records as $row) {
            $deviceId = (int) $row->device_id;
            $deviceName = $row->device_name ?: 'جهاز مجهول';

            if (! isset($devices[$deviceId])) {
                $devices[$deviceId] = [
                    'device_id' => $deviceId,
                    'device_name' => $deviceName,
                    'total_hours' => 0.0,
                    'total_sessions' => 0,
                    'total_revenue' => 0.0,
                    'play_types' => [],
                ];
            }

            $hours = round((float) ($row->total_minutes ?? 0.0) / 60, 2);
            $devices[$deviceId]['total_hours'] += $hours;
            $devices[$deviceId]['total_sessions'] += (int) $row->sessions_count;
            $devices[$deviceId]['total_revenue'] += (float) $row->total_revenue;

            $playType = $row->play_type ?: 'غير محدد';
            if (! isset($devices[$deviceId]['play_types'][$playType])) {
                $devices[$deviceId]['play_types'][$playType] = 0;
            }
            $devices[$deviceId]['play_types'][$playType] += (int) $row->sessions_count;
        }

        // Format total hours to nice numbers
        foreach ($devices as &$device) {
            $device['total_hours'] = round($device['total_hours'], 2);
        }

        $stats = array_values($devices);

        return $stats;
    }
}
