<?php

namespace App\Ai\Tools;

use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetReservationsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'جلب قائمة الحجوزات المسجلة للأجهزة، وتفاصيل أوقات الحجز والزبائن والحالات.';
    }

    public function handle(Request $request): Stringable|string
    {
        $dateStr = $request['date'] ?? null;
        $deviceName = $request['device_name'] ?? null;

        $query = \DB::table('reservations')
            ->leftJoin('devices', 'reservations.device_id', '=', 'devices.id')
            ->leftJoin('customers', 'reservations.customer_id', '=', 'customers.id')
            ->select(
                'reservations.id',
                'customers.name AS customer_name',
                'customers.phone AS customer_phone',
                'devices.name AS device_name',
                'reservations.start_time',
                'reservations.end_time',
                'reservations.status',
                'reservations.duration_minutes'
            );

        if ($dateStr) {
            $day = Carbon::parse($dateStr)->toDateString();
            $query->whereDate('reservations.start_time', $day);
        } else {
            // Default to today and upcoming reservations
            $query->whereDate('reservations.start_time', '>=', Carbon::today()->toDateString());
        }

        if ($deviceName) {
            $query->where('devices.name', 'like', '%'.$deviceName.'%');
        }

        $results = $query->orderBy('reservations.start_time', 'asc')->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'reservation_id' => $row->id,
                'customer_name' => $row->customer_name ?? 'عميل غير مسجل',
                'customer_phone' => $row->customer_phone ?? 'بدون هاتف',
                'device_name' => $row->device_name ?? 'أي جهاز',
                'start_time' => Carbon::parse($row->start_time)->toDateTimeString(),
                'end_time' => Carbon::parse($row->end_time)->toDateTimeString(),
                'duration_minutes' => (int) $row->duration_minutes,
                'status' => match ($row->status) {
                    'pending' => 'معلق',
                    'confirmed' => 'مؤكد',
                    'completed' => 'مكتمل',
                    'cancelled' => 'ملغي',
                    default => $row->status,
                },
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()
                ->description('التاريخ المطلوب فلترة الحجوزات بناء عليه YYYY-MM-DD (مثال: 2026-07-19) - اختياري.'),
            'device_name' => $schema->string()
                ->description('اسم الجهاز المطلوب البحث عن حجوزاته - اختياري.'),
        ];
    }
}
