<?php

namespace App\Ai\Tools;

use App\Support\ArabicSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCustomersBalancesTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search actual customers by tolerant Arabic name or phone and return their balances, debts, points, and membership levels.';
    }

    public function handle(Request $request): Stringable|string
    {
        $search = trim((string) ($request['search_name'] ?? ''));
        $onlyDebtors = (bool) ($request['only_debtors'] ?? false);
        $limit = min(max((int) ($request['limit'] ?? 25), 1), 50);

        $query = DB::table('customers')
            ->leftJoin('customer_levels', 'customers.customer_level_id', '=', 'customer_levels.id')
            ->select(
                'customers.id',
                'customers.name',
                'customers.phone',
                'customers.balance',
                'customers.points',
                'customer_levels.name AS level_name'
            )
            ->when($onlyDebtors, fn (Builder $builder): Builder => $builder->where('customers.balance', '<', 0))
            ->orderBy('customers.balance')
            ->orderBy('customers.id');

        $matches = [];
        $candidateNames = [];

        foreach ($query->cursor() as $customer) {
            if ($search !== '' && ! ArabicSearch::matches($customer->name, $search) && ! $this->phoneMatches($customer->phone, $search)) {
                if (count($candidateNames) < 100) {
                    $candidateNames[] = (string) $customer->name;
                }

                continue;
            }

            $balance = (float) $customer->balance;
            $matches[] = [
                'customer_id' => (int) $customer->id,
                'customer_name' => (string) $customer->name,
                'customer_phone' => $customer->phone,
                'balance' => $balance,
                'financial_status' => $balance < 0 ? 'debtor' : ($balance > 0 ? 'creditor' : 'settled'),
                'points' => (float) $customer->points,
                'level' => $customer->level_name,
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return json_encode([
            'status' => $matches === [] ? 'customer_not_matched' : 'customers_found',
            'search' => $search !== '' ? $search : null,
            'only_debtors' => $onlyDebtors,
            'customers' => $matches,
            'suggestions' => $search !== '' && $matches === []
                ? ArabicSearch::suggestions($candidateNames, $search)
                : [],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search_name' => $schema->string()->description('Customer name in Arabic or English, or phone number. Optional.'),
            'only_debtors' => $schema->boolean()->description('True to return customers with a negative balance only.'),
            'limit' => $schema->integer()->description('Maximum rows to return. Defaults to 25 and never exceeds 50.'),
        ];
    }

    private function phoneMatches(?string $phone, string $search): bool
    {
        $phoneDigits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        $searchDigits = preg_replace('/\D+/', '', $search) ?? '';

        return $searchDigits !== '' && str_contains($phoneDigits, $searchDigits);
    }
}
