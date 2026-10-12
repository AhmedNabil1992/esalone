<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use Illuminate\Support\Facades\DB;

class ResetTenantTrialDataAction
{
    /** @return array{deleted: array<string, int>, reset: array<string, int>} */
    public function execute(): array
    {
        $connection = DB::connection('tenant');

        return $connection->transaction(function () use ($connection): array {
            $deleted = [];

            foreach ($this->tablesInDeletionOrder() as $table) {
                $deleted[$table] = $connection->table($table)->delete();
            }

            return [
                'deleted' => $deleted,
                'reset' => [
                    'warehouse_stocks' => $connection->table('warehouse_stocks')->update([
                        'quantity' => 0,
                        'amount' => 0,
                        'updated_at' => now(),
                    ]),
                    'cash_boxes' => $connection->table('cash_boxes')->update([
                        'current_balance' => $connection->raw('opening_balance'),
                        'updated_at' => now(),
                    ]),
                    'customers' => $connection->table('customers')->update([
                        'balance' => 0,
                        'points' => 0,
                        'updated_at' => now(),
                    ]),
                    'devices' => $connection->table('devices')->update([
                        'status' => 'active',
                        'updated_at' => now(),
                    ]),
                ],
            ];
        });
    }

    /**
     * Child tables must appear before every parent table they reference.
     *
     * @return array<int, string>
     */
    private function tablesInDeletionOrder(): array
    {
        return [
            'agent_conversation_messages',
            'agent_conversations',
            'whatsapp_messages',
            'whatsapp_webhooks',
            'tax_histories',
            'service_histories',
            'discount_histories',
            'invoice_payments',
            'stock_movements',
            'market_history',
            'play_history',
            'invoices',
            'order_session_details',
            'play_session_details',
            'play_sessions',
            'purchase_items',
            'purchases',
            'customer_credits',
            'reservations',
            'waiting_lists',
            'device_control_commands',
            'expense_histories',
            'cash_box_ledger_entries',
            'shifts',
            'notifications',
        ];
    }
}
