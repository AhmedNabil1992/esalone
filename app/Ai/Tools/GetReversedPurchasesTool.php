<?php

namespace App\Ai\Tools;

use App\Support\Tenant\TenantClock;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetReversedPurchasesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Return actual reversed purchase invoices with their reference, supplier, warehouse, shift, amount, and purchase time.';
    }

    public function handle(Request $request): Stringable|string
    {
        $date = trim((string) ($request['date'] ?? ''));
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);
        $clock = app(TenantClock::class);

        $query = DB::table('purchases')
            ->leftJoin('suppliers', 'purchases.supplier_id', '=', 'suppliers.id')
            ->leftJoin('warehouses', 'purchases.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('shifts', 'purchases.shift_id', '=', 'shifts.id')
            ->where('purchases.status', 'reversed')
            ->when($this->activeBranchId(), fn (Builder $builder, int $branchId): Builder => $builder->where('purchases.branch_id', $branchId))
            ->select(
                'purchases.id',
                'purchases.reference',
                'purchases.total_amount',
                'purchases.purchased_at',
                'suppliers.name AS supplier_name',
                'warehouses.name AS warehouse_name',
                'shifts.shift_no'
            )
            ->orderByDesc('purchases.purchased_at')
            ->orderByDesc('purchases.id')
            ->limit($limit);

        if ($date !== '') {
            [$startUtc, $endUtc] = $clock->localDateRangeUtc($date);
            $query->whereBetween('purchases.purchased_at', [$startUtc, $endUtc]);
        }

        $purchases = [];
        foreach ($query->get() as $purchase) {
            $purchases[] = [
                'purchase_id' => (int) $purchase->id,
                'reference' => $purchase->reference,
                'supplier' => $purchase->supplier_name,
                'warehouse' => $purchase->warehouse_name,
                'shift_number' => $purchase->shift_no !== null ? (int) $purchase->shift_no : null,
                'total_amount' => (float) $purchase->total_amount,
                'purchased_at' => $purchase->purchased_at
                    ? $clock->utcToLocal($purchase->purchased_at)->toDateTimeString()
                    : null,
            ];
        }

        return json_encode([
            'status' => $purchases === [] ? 'no_reversed_purchases_in_scope' : 'reversed_purchases_found',
            'date' => $date !== '' ? $date : null,
            'count' => count($purchases),
            'purchases' => $purchases,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'date' => $schema->string()->description('Optional local purchase date in YYYY-MM-DD format.'),
            'limit' => $schema->integer()->description('Maximum rows to return. Defaults to 25 and never exceeds 50.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
