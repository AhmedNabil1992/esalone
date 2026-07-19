<?php

namespace App\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCustomersBalancesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'جلب أرصدة وحسابات ومديونيات العملاء ونقاط الولاء الخاصة بهم وفئات اشتراكاتهم.';
    }

    public function handle(Request $request): Stringable|string
    {
        $searchName = $request['search_name'] ?? null;
        $onlyDebtors = $request['only_debtors'] ?? false;

        $query = DB::table('customers')
            ->leftJoin('customer_levels', 'customers.customer_level_id', '=', 'customer_levels.id')
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.balance',
                'customers.points',
                'customer_levels.name AS level_name'
            );

        if ($searchName) {
            $query->where('customers.name', 'like', '%'.$searchName.'%')
                ->orWhere('customers.phone', 'like', '%'.$searchName.'%');
        }

        if ($onlyDebtors) {
            $query->where('customers.balance', '<', 0);
        }

        $results = $query->orderBy('customers.balance', 'asc')->get();

        $output = [];
        foreach ($results as $row) {
            $balance = (float) $row->balance;

            $output[] = [
                'customer_id' => $row->id,
                'customer_name' => $row->name,
                'customer_phone' => $row->phone ?? 'بدون هاتف',
                'balance' => $balance,
                'financial_status' => $balance < 0 ? 'عليه مديونية (دين)' : ($balance > 0 ? 'له رصيد (دائن)' : 'خالص الحساب'),
                'points' => (float) $row->points,
                'level' => $row->level_name ?? 'فئة افتراضية',
            ];
        }

        return json_encode($output, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search_name' => $schema->string()
                ->description('اسم العميل أو رقم هاتفه المطلوب البحث عنه - اختياري.'),
            'only_debtors' => $schema->boolean()
                ->description('تحديد true لعرض العملاء الذين عليهم مديونيات فقط (رصيد سالب).'),
        ];
    }
}
