<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCashBoxesBalancesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'الحصول على قائمة بجميع الخزائن المتاحة في المحل (مثل الدرج الرئيسي، الخزنة الفرعية) وأرصدة المبالغ الحالية بكل منها.';
    }

    public function handle(Request $request): Stringable|string
    {
        $results = DB::table('cash_boxes')
            ->where('is_active', 1)
            ->when($this->activeBranchId(), fn (Builder $query, int $branchId): Builder => $query->where('branch_id', $branchId))
            ->select('id', 'name', 'code', 'type', 'opening_balance', 'current_balance')
            ->orderBy('id')
            ->limit(50)
            ->get();

        $output = [];
        foreach ($results as $row) {
            $output[] = [
                'cashbox_id' => $row->id,
                'name' => $row->name,
                'code' => $row->code,
                'type' => $row->type === 'main' ? 'رئيسية' : 'فرعية',
                'opening_balance' => (float) $row->opening_balance,
                'current_balance' => (float) $row->current_balance,
            ];
        }

        return json_encode([
            'status' => $output === [] ? 'no_active_cash_boxes' : 'cash_boxes_found',
            'branch_id' => $this->activeBranchId(),
            'cash_boxes' => $output,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return []; // No arguments required
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return $branchId !== null && $branchId !== 'all' ? (int) $branchId : null;
    }
}
