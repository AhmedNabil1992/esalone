<?php

namespace App\Ai\Tools;

use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Shift;
use App\Support\ArabicSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetItemOrderLocationsTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'معرفة الأجهزة أو الترابيزات التي طلبت صنفاً معيناً، مع فصل الطلبات المفتوحة حالياً عن الطلبات التاريخية أو طلبات وردية محددة.';
    }

    public function handle(Request $request): Stringable|string
    {
        $itemName = trim((string) ($request['item_name'] ?? ''));
        $shiftNo = $request['shift_no'] ?? null;

        if ($itemName === '') {
            return json_encode(['status' => 'item_name_required'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $catalogItems = CatalogItem::query()
            ->where('is_active', true)
            ->get(['id', 'name']);
        $matchedItems = $catalogItems
            ->filter(fn (CatalogItem $item): bool => ArabicSearch::matches($item->name, $itemName))
            ->values();

        if ($matchedItems->isEmpty()) {
            return json_encode([
                'status' => 'catalog_item_not_matched',
                'searched_for' => $itemName,
                'suggestions' => ArabicSearch::suggestions($catalogItems->pluck('name'), $itemName),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        $itemIds = $matchedItems->pluck('id');
        $activeLocations = collect();

        if (! $shiftNo) {
            $activeLocations = DB::table('order_session_details')
                ->join('play_sessions', 'order_session_details.play_session_id', '=', 'play_sessions.id')
                ->join('devices', 'order_session_details.device_id', '=', 'devices.id')
                ->join('catalog_items', 'order_session_details.catalog_item_id', '=', 'catalog_items.id')
                ->where('play_sessions.status', 'active')
                ->where('order_session_details.status', '!=', 'cancelled')
                ->whereIn('order_session_details.catalog_item_id', $itemIds)
                ->when($this->activeBranchId(), fn ($query, int $branchId) => $query->where('play_sessions.branch_id', $branchId))
                ->select(
                    'devices.id AS device_id',
                    'devices.name AS device_name',
                    'catalog_items.name AS item_name',
                    DB::raw('SUM(order_session_details.quantity) AS quantity'),
                    DB::raw('SUM(order_session_details.total) AS total')
                )
                ->groupBy('devices.id', 'devices.name', 'catalog_items.name')
                ->orderBy('devices.name')
                ->limit(20)
                ->get();
        }

        $historyQuery = DB::table('market_history')
            ->join('devices', 'market_history.device_id', '=', 'devices.id')
            ->leftJoin('catalog_items', 'market_history.catalog_item_id', '=', 'catalog_items.id')
            ->leftJoin('shifts', 'market_history.shift_id', '=', 'shifts.id')
            ->whereIn('market_history.catalog_item_id', $itemIds)
            ->when($this->activeBranchId(), fn ($query, int $branchId) => $query->where('market_history.branch_id', $branchId));

        if ($shiftNo) {
            $shift = Shift::query()->where('shift_no', $shiftNo)->first();
            if (! $shift) {
                return json_encode([
                    'status' => 'shift_not_found',
                    'shift_no' => (int) $shiftNo,
                    'matched_catalog_items' => $matchedItems->pluck('name')->all(),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }
            $historyQuery->where('market_history.shift_id', $shift->id);
        }

        $historicalLocations = $historyQuery
            ->select(
                'devices.id AS device_id',
                'devices.name AS device_name',
                DB::raw('COALESCE(catalog_items.name, market_history.subject_name) AS item_name'),
                'shifts.shift_no',
                DB::raw('SUM(market_history.quantity) AS quantity'),
                DB::raw('SUM(market_history.total_amount) AS total'),
                DB::raw('MAX(market_history.transaction_date) AS last_order_date')
            )
            ->groupBy('devices.id', 'devices.name', 'catalog_items.name', 'market_history.subject_name', 'shifts.shift_no')
            ->orderByDesc('last_order_date')
            ->limit(20)
            ->get();

        return json_encode([
            'status' => $this->resultStatus($activeLocations, $historicalLocations),
            'searched_for' => $itemName,
            'matched_catalog_items' => $matchedItems->pluck('name')->all(),
            'scope' => $shiftNo ? 'الوردية رقم '.(int) $shiftNo : 'الحالي ثم أحدث السجلات التاريخية',
            'active_locations' => $activeLocations,
            'historical_locations' => $historicalLocations,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'item_name' => $schema->string()->description('اسم الصنف كما كتبه المستخدم، مثل قهوة فرنساوي.')->required(),
            'shift_no' => $schema->integer()->description('رقم الوردية إذا طلب المستخدم وردية محددة.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }

    /**
     * @param  Collection<int, \stdClass>  $active
     * @param  Collection<int, \stdClass>  $history
     */
    private function resultStatus(Collection $active, Collection $history): string
    {
        return match (true) {
            $active->isNotEmpty() => 'active_orders_found',
            $history->isNotEmpty() => 'historical_orders_only',
            default => 'item_exists_but_no_orders_in_scope',
        };
    }
}
