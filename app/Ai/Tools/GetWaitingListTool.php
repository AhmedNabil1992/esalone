<?php

namespace App\Ai\Tools;

use App\Support\ArabicSearch;
use App\Support\Tenant\TenantClock;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetWaitingListTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Return customers whose actual waiting-list status is waiting, optionally matching an Arabic or English device type.';
    }

    public function handle(Request $request): Stringable|string
    {
        $deviceTypeName = trim((string) ($request['device_type_name'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);
        $clock = app(TenantClock::class);
        $now = $clock->nowUtc();

        $query = DB::table('waiting_lists')
            ->leftJoin('devices_types', 'waiting_lists.devices_type_id', '=', 'devices_types.id')
            ->select(
                'waiting_lists.id',
                'waiting_lists.customer_name',
                'waiting_lists.customer_phone',
                'devices_types.name AS device_type_name',
                'waiting_lists.notes',
                'waiting_lists.created_at'
            )
            ->where('waiting_lists.status', 'waiting')
            ->when($this->activeBranchId(), fn (Builder $builder, int $branchId): Builder => $builder->where('waiting_lists.branch_id', $branchId))
            ->orderBy('waiting_lists.created_at');

        $waiting = [];
        $candidateTypes = [];

        foreach ($query->cursor() as $row) {
            $typeName = (string) ($row->device_type_name ?? '');
            if ($deviceTypeName !== '' && ! ArabicSearch::matches($typeName, $deviceTypeName)) {
                $candidateTypes[] = $typeName;

                continue;
            }

            $createdUtc = CarbonImmutable::parse($row->created_at, 'UTC');
            $waiting[] = [
                'waiting_id' => (int) $row->id,
                'customer_name' => (string) $row->customer_name,
                'customer_phone' => $row->customer_phone,
                'requested_device_type' => $row->device_type_name,
                'status' => 'waiting',
                'notes' => $row->notes,
                'waiting_since' => $clock->utcToLocal($createdUtc)->toDateTimeString(),
                'waiting_duration_minutes' => max(0, (int) ceil($createdUtc->diffInSeconds($now) / 60)),
            ];

            if (count($waiting) >= $limit) {
                break;
            }
        }

        return json_encode([
            'status' => $waiting === [] ? 'no_waiting_customers_matched' : 'waiting_customers_found',
            'device_type_search' => $deviceTypeName !== '' ? $deviceTypeName : null,
            'waiting_customers' => $waiting,
            'suggestions' => $deviceTypeName !== '' && $waiting === []
                ? ArabicSearch::suggestions(array_values(array_unique($candidateTypes)), $deviceTypeName)
                : [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'device_type_name' => $schema->string()->description('Optional device type name in Arabic or English.'),
            'limit' => $schema->integer()->description('Maximum rows to return. Defaults to 25 and never exceeds 50.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
