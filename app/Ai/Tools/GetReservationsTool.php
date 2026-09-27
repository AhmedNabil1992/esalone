<?php

namespace App\Ai\Tools;

use App\Support\ArabicSearch;
use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetReservationsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Return actual device reservations with customer, device, time, duration, and status details.';
    }

    public function handle(Request $request): Stringable|string
    {
        $date = trim((string) ($request['date'] ?? ''));
        $deviceName = trim((string) ($request['device_name'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);
        $clock = app(TenantClock::class);

        $query = DB::table('reservations')
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
            )
            ->when($this->activeBranchId(), fn (Builder $builder, int $branchId): Builder => $builder->where('reservations.branch_id', $branchId));

        if ($date !== '') {
            [$dayStart, $dayEnd] = $clock->localDateRangeUtc($date);
            $query->whereBetween('reservations.start_time', [$dayStart, $dayEnd]);
        } else {
            $query->where('reservations.start_time', '>=', $clock->localDateStartUtc($clock->nowLocal()));
        }

        $reservations = [];
        $candidateDevices = [];

        foreach ($query->orderBy('reservations.start_time')->cursor() as $row) {
            $actualDeviceName = (string) ($row->device_name ?? '');
            if ($deviceName !== '' && ! ArabicSearch::matches($actualDeviceName, $deviceName)) {
                $candidateDevices[] = $actualDeviceName;

                continue;
            }

            $reservations[] = [
                'reservation_id' => (int) $row->id,
                'customer_name' => $row->customer_name,
                'customer_phone' => $row->customer_phone,
                'device_name' => $row->device_name,
                'start_time' => $clock->utcToLocal($row->start_time)->toDateTimeString(),
                'end_time' => $clock->utcToLocal($row->end_time)->toDateTimeString(),
                'duration_minutes' => $row->duration_minutes !== null ? (int) $row->duration_minutes : null,
                'status' => match ($row->status) {
                    'active' => 'active',
                    'completed' => 'completed',
                    'cancelled' => 'cancelled',
                    default => (string) $row->status,
                },
            ];

            if (count($reservations) >= $limit) {
                break;
            }
        }

        return json_encode([
            'status' => $reservations === [] ? 'no_reservations_matched' : 'reservations_found',
            'date' => $date !== '' ? $date : null,
            'device_search' => $deviceName !== '' ? $deviceName : null,
            'reservations' => $reservations,
            'suggestions' => $deviceName !== '' && $reservations === []
                ? ArabicSearch::suggestions(array_values(array_unique($candidateDevices)), $deviceName)
                : [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Optional local date in YYYY-MM-DD format.'),
            'device_name' => $schema->string()->description('Optional device name in Arabic or English.'),
            'limit' => $schema->integer()->description('Maximum rows to return. Defaults to 25 and never exceeds 50.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
