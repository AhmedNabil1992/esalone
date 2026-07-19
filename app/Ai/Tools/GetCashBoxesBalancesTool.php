<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
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
            ->select('id', 'name', 'code', 'type', 'opening_balance', 'current_balance')
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

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return []; // No arguments required
    }
}
