<?php

namespace App\Ai\Tools;

use App\Models\Tenant\PlayHistory;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetMostActiveDevicesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على تقرير بالأجهزة الأكثر استخداماً وتشغيلاً (مجموع دقائق اللعب وعدد الجلسات الإجمالي) خلال فترة محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $startDateStr = $request['start_date'] ?? null;
        $endDateStr = $request['end_date'] ?? null;

        $query = PlayHistory::query()
            ->leftJoin('devices', 'play_history.device_id', '=', 'devices.id')
            ->select(
                'devices.name AS device_name',
                DB::raw('SUM(play_history.play_time) AS total_minutes'),
                DB::raw('COUNT(*) AS total_sessions'),
                DB::raw('SUM(play_history.cost) AS total_earnings')
            );

        if ($startDateStr || $endDateStr) {
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfDay() : Carbon::now()->endOfDay();
            $query->whereBetween('play_history.trx_date', [$start->toDateString(), $end->toDateString()]);
        }

        $results = $query->groupBy('devices.id', 'devices.name')
            ->orderBy('total_minutes', 'desc')
            ->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'device_name' => $row->device_name ?? 'جهاز غير معروف',
                'total_minutes' => (int) $row->total_minutes,
                'total_hours' => round($row->total_minutes / 60.0, 1),
                'total_sessions' => (int) $row->total_sessions,
                'total_earnings' => round((float) $row->total_earnings, 2),
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'start_date' => $schema->string()
                ->description('تاريخ البداية لتقرير إحصائيات الأجهزة YYYY-MM-DD'),
            'end_date' => $schema->string()
                ->description('تاريخ النهاية لتقرير إحصائيات الأجهزة YYYY-MM-DD'),
        ];
    }
}
