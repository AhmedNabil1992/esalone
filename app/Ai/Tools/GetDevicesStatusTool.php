<?php

namespace App\Ai\Tools;

use App\Models\Tenant\Devices;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlaySessionDetail;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetDevicesStatusTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على حالة الأجهزة النشطة (المشغولة والمتاحة)، وتفاصيل الجلسات المفتوحة، وحساب وقت وتكلفة اللعب الجاري، والطلبات الحالية لكل جهاز.';
    }

    public function handle(Request $request): Stringable|string
    {
        $deviceName = $request['device_name'] ?? null;

        $devicesQuery = Devices::query()->with(['activeSession']);
        if ($deviceName) {
            $devicesQuery->where('name', 'like', '%'.$deviceName.'%');
        }
        $devices = $devicesQuery->get();

        $output = [];
        $now = Carbon::now();

        foreach ($devices as $device) {
            $deviceStatus = $device->status ?? ($device->activeSession ? 'busy' : 'available');

            $deviceData = [
                'device_id' => $device->id,
                'device_name' => $device->name,
                'status' => $deviceStatus === 'busy' ? 'مشغول' : 'متاح',
                'session' => null,
            ];

            if ($device->activeSession) {
                $session = $device->activeSession;
                $details = PlaySessionDetail::where('play_session_id', $session->id)->get();
                $orders = OrderSessionDetail::where('play_session_id', $session->id)
                    ->where('status', '!=', 'cancelled')
                    ->get();

                $completedPlayCost = 0.0;
                $activePlayCost = 0.0;
                $sessionDetailsList = [];

                foreach ($details as $detail) {
                    $start = Carbon::parse($detail->start_time);
                    $end = $detail->end_time ? Carbon::parse($detail->end_time) : null;

                    if ($end) {
                        // Completed period
                        $durationMinutes = $detail->period ?? (int) ceil($start->diffInSeconds($end) / 60);
                        $cost = (float) $detail->cost;
                        $completedPlayCost += $cost;
                    } else {
                        // Active period running now
                        $durationMinutes = (int) ceil($start->diffInSeconds($now) / 60);
                        $cost = round(($start->diffInSeconds($now) / 3600.0) * (float) $detail->play_price, 2);
                        $activePlayCost += $cost;
                    }

                    $sessionDetailsList[] = [
                        'play_type' => $detail->play_type,
                        'play_category' => $detail->play_category,
                        'start_time' => $start->toDateTimeString(),
                        'end_time' => $end ? $end->toDateTimeString() : 'مستمر الآن',
                        'price_per_hour' => (float) $detail->play_price,
                        'duration_minutes' => $durationMinutes,
                        'cost' => $cost,
                    ];
                }

                $ordersList = [];
                $totalOrdersCost = 0.0;
                foreach ($orders as $order) {
                    $orderTotal = (float) $order->total;
                    $totalOrdersCost += $orderTotal;
                    $ordersList[] = [
                        'item_name' => $order->catalogItem?->name ?? 'طلب كافيه',
                        'quantity' => (int) $order->quantity,
                        'price' => (float) $order->price,
                        'total' => $orderTotal,
                    ];
                }

                $totalPlayCost = $completedPlayCost + $activePlayCost;

                $deviceData['session'] = [
                    'session_id' => $session->id,
                    'start_time' => Carbon::parse($session->created_at)->toDateTimeString(),
                    'prepaid_amount' => (float) $session->prepaid_amount,
                    'play_periods' => $sessionDetailsList,
                    'orders' => $ordersList,
                    'completed_play_cost' => round($completedPlayCost, 2),
                    'active_play_cost' => round($activePlayCost, 2),
                    'total_play_cost' => round($totalPlayCost, 2),
                    'total_orders_cost' => round($totalOrdersCost, 2),
                    'total_session_cost' => round($totalPlayCost + $totalOrdersCost, 2),
                ];
            }

            $output[] = $deviceData;
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device_name' => $schema->string()
                ->description('اسم الجهاز المطلوب البحث عنه (مثال: Room 1, بلياردو 1) - اختياري لتصفية النتائج.'),
        ];
    }
}
