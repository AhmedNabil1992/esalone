<?php

namespace App\Ai\Tools;

use App\Models\Tenant\WaitingList;
use Carbon\Carbon;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetWaitingListTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'جلب الأشخاص المسجلين في قائمة الانتظار للأجهزة والألعاب، مع حساب وقت الانتظار وتفاصيل طلباتهم.';
    }

    public function handle(Request $request): Stringable|string
    {
        $deviceTypeName = $request['device_type_name'] ?? null;

        // Note: WaitingList might be named WaitingLists or WaitingList. Let's make sure it's database-safe.
        // We can query using the DB query builder or the model to prevent class name mismatches.
        $query = \DB::table('waiting_lists')
            ->leftJoin('devices_types', 'waiting_lists.devices_type_id', '=', 'devices_types.id')
            ->select(
                'waiting_lists.id',
                'waiting_lists.customer_name',
                'waiting_lists.customer_phone',
                'devices_types.name AS device_type_name',
                'waiting_lists.status',
                'waiting_lists.notes',
                'waiting_lists.created_at'
            )
            ->whereIn('waiting_lists.status', ['waiting', 'pending']); // common waiting statuses

        if ($deviceTypeName) {
            $query->where('devices_types.name', 'like', '%'.$deviceTypeName.'%');
        }

        $results = $query->orderBy('waiting_lists.created_at', 'asc')->get();

        $output = [];
        $now = Carbon::now();

        foreach ($results as $row) {
            $created = Carbon::parse($row->created_at);
            $waitingMinutes = (int) ceil($created->diffInSeconds($now) / 60);

            $output[] = [
                'waiting_id' => $row->id,
                'customer_name' => $row->customer_name,
                'customer_phone' => $row->customer_phone,
                'requested_device_type' => $row->device_type_name ?? 'أي جهاز',
                'status' => $row->status === 'waiting' || $row->status === 'pending' ? 'انتظار' : $row->status,
                'notes' => $row->notes,
                'waiting_since' => $created->toDateTimeString(),
                'waiting_duration_minutes' => $waitingMinutes,
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device_type_name' => $schema->string()
                ->description('نوع الجهاز المطلوب فلترة الانتظار بناء عليه (مثال: بلياردو، بلايستيشن) - اختياري.'),
        ];
    }
}
