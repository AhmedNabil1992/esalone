<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CloseSessionInvoiceAction;
use App\Actions\Tenant\CreateDirectSaleAction;
use App\Actions\Tenant\StartSessionAction;
use App\Actions\Tenant\SwitchDeviceAction;
use App\Actions\Tenant\SwitchPlayAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant\CashBox;
use App\Models\Tenant\CatalogItem;
use App\Models\Tenant\Customer;
use App\Models\Tenant\CustomerLevel;
use App\Models\Tenant\Devices;
use App\Models\Tenant\DevicesType;
use App\Models\Tenant\ExpensesType;
use App\Models\Tenant\InventoryGroup;
use App\Models\Tenant\Offer;
use App\Models\Tenant\OrderSessionDetail;
use App\Models\Tenant\PlayPrice;
use App\Models\Tenant\PlaySessionDetail;
use App\Models\Tenant\PlaySessions;
use App\Models\Tenant\PlayType;
use App\Models\Tenant\PosTerminal;
use App\Models\Tenant\ProductComponent;
use App\Models\Tenant\Reservation;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StockMovement;
use App\Models\Tenant\Supplier;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Models\Tenant\WaitingList;
use App\Models\Tenant\Warehouse;
use App\Models\Tenant\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncController extends Controller
{
    public function init(): JsonResponse
    {
        $schemas = [
            'users' => 'CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT, role TEXT)',
            'devices_types' => 'CREATE TABLE IF NOT EXISTS devices_types (id INTEGER PRIMARY KEY, name TEXT, extra_time INTEGER)',
            'devices' => 'CREATE TABLE IF NOT EXISTS devices (id INTEGER PRIMARY KEY, name TEXT, status TEXT, devices_type_id INTEGER, is_active INTEGER)',
            'play_sessions' => 'CREATE TABLE IF NOT EXISTS play_sessions (id TEXT PRIMARY KEY, device_id INTEGER, status TEXT, customer_id INTEGER, prepaid_amount REAL, start_time TEXT, end_time TEXT, total_amount REAL, created_at TEXT)',
            'play_session_details' => 'CREATE TABLE IF NOT EXISTS play_session_details (id TEXT PRIMARY KEY, play_session_id TEXT, play_price_id INTEGER, play_price REAL, start_time TEXT, created_at TEXT)',
            'play_types' => 'CREATE TABLE IF NOT EXISTS play_types (id INTEGER PRIMARY KEY, name TEXT)',
            'play_prices' => 'CREATE TABLE IF NOT EXISTS play_prices (id INTEGER PRIMARY KEY, device_id INTEGER, play_type_id INTEGER, price REAL)',
            'inventory_groups' => 'CREATE TABLE IF NOT EXISTS inventory_groups (id INTEGER PRIMARY KEY, name TEXT)',
            'catalog_items' => 'CREATE TABLE IF NOT EXISTS catalog_items (id INTEGER PRIMARY KEY, name TEXT, barcode TEXT, sale_price REAL, inventory_group_id INTEGER, is_active INTEGER)',
            'product_components' => 'CREATE TABLE IF NOT EXISTS product_components (id INTEGER PRIMARY KEY, parent_id INTEGER, component_id INTEGER, quantity REAL)',
            'warehouses' => 'CREATE TABLE IF NOT EXISTS warehouses (id INTEGER PRIMARY KEY, name TEXT)',
            'cash_boxes' => 'CREATE TABLE IF NOT EXISTS cash_boxes (id INTEGER PRIMARY KEY, name TEXT, current_balance REAL)',
            'suppliers' => 'CREATE TABLE IF NOT EXISTS suppliers (id INTEGER PRIMARY KEY, name TEXT)',
            'purchases' => 'CREATE TABLE IF NOT EXISTS purchases (id INTEGER PRIMARY KEY, supplier_id INTEGER, invoice_no TEXT, total_amount REAL)',
            'purchase_items' => 'CREATE TABLE IF NOT EXISTS purchase_items (id INTEGER PRIMARY KEY, purchase_id INTEGER, item_id INTEGER, qty REAL)',
            'warehouse_stocks' => 'CREATE TABLE IF NOT EXISTS warehouse_stocks (id INTEGER PRIMARY KEY, warehouse_id INTEGER, catalog_item_id INTEGER, qty REAL)',
            'stock_movements' => 'CREATE TABLE IF NOT EXISTS stock_movements (id INTEGER PRIMARY KEY, warehouse_id INTEGER, catalog_item_id INTEGER, type TEXT, quantity REAL)',
            'system_configs' => 'CREATE TABLE IF NOT EXISTS system_configs (id INTEGER PRIMARY KEY, key TEXT, value TEXT)',
            'shifts' => 'CREATE TABLE IF NOT EXISTS shifts (id INTEGER PRIMARY KEY, status TEXT, open_time TEXT, close_time TEXT, start_balance REAL, end_balance REAL)',
            'customers' => 'CREATE TABLE IF NOT EXISTS customers (id INTEGER PRIMARY KEY, name TEXT, phone TEXT, customer_level_id INTEGER, balance REAL, points REAL, is_active INTEGER)',
            'customer_credits' => 'CREATE TABLE IF NOT EXISTS customer_credits (id INTEGER PRIMARY KEY, customer_id INTEGER, amount REAL, type TEXT)',
            'expenses_types' => 'CREATE TABLE IF NOT EXISTS expenses_types (id INTEGER PRIMARY KEY, name TEXT)',
            'cash_box_ledger_entries' => 'CREATE TABLE IF NOT EXISTS cash_box_ledger_entries (id TEXT PRIMARY KEY, cash_box_id INTEGER, type TEXT, amount REAL, description TEXT, created_at TEXT)',
            'reservations' => 'CREATE TABLE IF NOT EXISTS reservations (id INTEGER PRIMARY KEY, customer_name TEXT, start_time TEXT, device_id INTEGER, date TEXT)',
            'order_session_details' => 'CREATE TABLE IF NOT EXISTS order_session_details (id TEXT PRIMARY KEY, play_session_id TEXT, catalog_item_id INTEGER, quantity REAL, unit_price REAL, total_amount REAL, created_at TEXT)',
            'offers' => 'CREATE TABLE IF NOT EXISTS offers (id INTEGER PRIMARY KEY, name TEXT, discount_percent REAL)',
            'device_control_commands' => 'CREATE TABLE IF NOT EXISTS device_control_commands (id INTEGER PRIMARY KEY, command TEXT, status TEXT)',
            'invoices' => 'CREATE TABLE IF NOT EXISTS invoices (id TEXT PRIMARY KEY, invoice_no TEXT, play_session_id TEXT, device_id INTEGER, total_amount REAL, discount_amount REAL, paid_amount REAL, status TEXT, created_at TEXT)',
            'invoice_payments' => 'CREATE TABLE IF NOT EXISTS invoice_payments (id TEXT PRIMARY KEY, invoice_id TEXT, amount REAL, payment_method TEXT, cash_box_id INTEGER, created_at TEXT)',
            'pos_terminals' => 'CREATE TABLE IF NOT EXISTS pos_terminals (id INTEGER PRIMARY KEY, name TEXT)',
            'push_subscriptions' => 'CREATE TABLE IF NOT EXISTS push_subscriptions (id INTEGER PRIMARY KEY, endpoint TEXT)',
            'notifications' => 'CREATE TABLE IF NOT EXISTS notifications (id INTEGER PRIMARY KEY, title TEXT, body TEXT)',
            'waiting_lists' => 'CREATE TABLE IF NOT EXISTS waiting_lists (id INTEGER PRIMARY KEY, customer_name TEXT, notes TEXT, time TEXT)',
            'customer_levels' => 'CREATE TABLE IF NOT EXISTS customer_levels (id INTEGER PRIMARY KEY, name TEXT, discount_percent REAL)',
            'tax_histories' => 'CREATE TABLE IF NOT EXISTS tax_histories (id INTEGER PRIMARY KEY, amount REAL)',
            'service_histories' => 'CREATE TABLE IF NOT EXISTS service_histories (id INTEGER PRIMARY KEY, amount REAL)',
            'discount_histories' => 'CREATE TABLE IF NOT EXISTS discount_histories (id INTEGER PRIMARY KEY, amount REAL)',
            'play_history' => 'CREATE TABLE IF NOT EXISTS play_history (id INTEGER PRIMARY KEY, total_amount REAL)',
            'market_history' => 'CREATE TABLE IF NOT EXISTS market_history (id INTEGER PRIMARY KEY, total_amount REAL)',

            // Spatie Permissions
            'permissions' => 'CREATE TABLE IF NOT EXISTS permissions (id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT)',
            'roles' => 'CREATE TABLE IF NOT EXISTS roles (id INTEGER PRIMARY KEY, name TEXT, guard_name TEXT)',
            'model_has_permissions' => 'CREATE TABLE IF NOT EXISTS model_has_permissions (permission_id INTEGER, model_type TEXT, model_id INTEGER, PRIMARY KEY(permission_id, model_type, model_id))',
            'model_has_roles' => 'CREATE TABLE IF NOT EXISTS model_has_roles (role_id INTEGER, model_type TEXT, model_id INTEGER, PRIMARY KEY(role_id, model_type, model_id))',
            'role_has_permissions' => 'CREATE TABLE IF NOT EXISTS role_has_permissions (permission_id INTEGER, role_id INTEGER, PRIMARY KEY(permission_id, role_id))',

            // local metadata table
            'local_metadata' => 'CREATE TABLE IF NOT EXISTS local_metadata (key TEXT PRIMARY KEY, value TEXT)',
        ];

        $data = [
            'users' => User::all(),
            'devices_types' => DevicesType::all(),
            'devices' => Devices::query()->where('is_active', true)->get(),
            'play_sessions' => PlaySessions::query()->where('status', 'active')->get(),
            'play_session_details' => PlaySessionDetail::query()
                ->whereIn('play_session_id', PlaySessions::query()->where('status', 'active')->select('id'))
                ->get(),
            'play_types' => PlayType::all(),
            'play_prices' => PlayPrice::all(),
            'inventory_groups' => InventoryGroup::query()->where('is_active', true)->where('name', '!=', 'مكونات')->get(),
            'catalog_items' => CatalogItem::query()->where('is_active', true)->get(),
            'product_components' => ProductComponent::all(),
            'warehouses' => Warehouse::all(),
            'cash_boxes' => CashBox::all(),
            'suppliers' => Supplier::all(),
            'warehouse_stocks' => WarehouseStock::all(),
            'stock_movements' => StockMovement::all(),
            'system_configs' => SystemConfig::all(),
            'shifts' => array_filter([Shift::query()->where('status', 'open')->latest()->first() ?? Shift::latest()->first()]),
            'customers' => Customer::query()->where('is_active', true)->get(),
            'expenses_types' => ExpensesType::all(),
            'order_session_details' => OrderSessionDetail::query()
                ->whereIn('play_session_id', PlaySessions::query()->where('status', 'active')->select('id'))
                ->get(),
            'offers' => Offer::all(),
            'device_control_commands' => DB::connection('tenant')->table('device_control_commands')->get(),
            'pos_terminals' => PosTerminal::all(),
            'push_subscriptions' => DB::connection('tenant')->table('push_subscriptions')->get(),
            'waiting_lists' => WaitingList::all(),
            'customer_levels' => CustomerLevel::all(),
            'reservations' => Reservation::all(),

            // Permissions
            'permissions' => DB::connection('tenant')->table('permissions')->get(),
            'roles' => DB::connection('tenant')->table('roles')->get(),
            'model_has_permissions' => DB::connection('tenant')->table('model_has_permissions')->get(),
            'model_has_roles' => DB::connection('tenant')->table('model_has_roles')->get(),
            'role_has_permissions' => DB::connection('tenant')->table('role_has_permissions')->get(),
        ];

        return response()->json([
            'schemas' => $schemas,
            'data' => $data,
            'subscription_expiry' => tenant()?->ended_at ? tenant()->ended_at->toIso8601String() : null,
            'server_timestamp' => now()->toIso8601String(),
        ]);
    }

    public function sync(Request $request): JsonResponse
    {
        $queue = $request->input('queue', []);
        $results = [];
        $actor = Auth::user();

        if (! $actor) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        foreach ($queue as $index => $op) {
            $type = $op['type'] ?? null;
            $data = $op['data'] ?? [];
            $opId = $op['id'] ?? $index;

            try {
                switch ($type) {
                    case 'START_SESSION':
                        $action = new StartSessionAction;
                        if (isset($data['start_time'])) {
                            $action->customStartTime = Carbon::parse($data['start_time']);
                        }
                        $action->execute(
                            (int) $data['device_id'],
                            (int) $data['play_price_id'],
                            $actor,
                            isset($data['duration_minutes']) ? (int) $data['duration_minutes'] : null,
                            isset($data['customer_id']) ? (int) $data['customer_id'] : null
                        );
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'SWITCH_PLAY':
                        $action = new SwitchPlayAction;
                        if (isset($data['switch_time'])) {
                            $action->customSwitchTime = Carbon::parse($data['switch_time']);
                        }
                        $action->execute(
                            (int) $data['device_id'],
                            (int) $data['new_play_price_id'],
                            $actor
                        );
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'SWITCH_DEVICE':
                        $action = new SwitchDeviceAction;
                        if (isset($data['switch_time'])) {
                            $action->customSwitchTime = Carbon::parse($data['switch_time']);
                        }
                        $action->execute(
                            (int) $data['old_device_id'],
                            (int) $data['new_device_id'],
                            $actor
                        );
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'CLOSE_SESSION':
                        $action = new CloseSessionInvoiceAction;
                        if (isset($data['close_time'])) {
                            $action->customCloseTime = Carbon::parse($data['close_time']);
                        }
                        $action->execute(
                            (int) $data['device_id'],
                            $actor,
                            (float) ($data['paid_amount'] ?? 0),
                            (float) ($data['discount_amount'] ?? 0),
                            $data['discount_reason'] ?? null,
                            $data['payment_allocations'] ?? null,
                            isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                            (bool) ($data['deposit_remainder_to_customer_balance'] ?? false)
                        );
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'CREATE_DIRECT_SALE':
                        $action = new CreateDirectSaleAction;
                        $action->execute(
                            $data['items'] ?? [],
                            isset($data['customer_id']) ? (int) $data['customer_id'] : null,
                            isset($data['cash_box_id']) ? (int) $data['cash_box_id'] : null,
                            (float) ($data['discount_amount'] ?? 0),
                            $actor,
                            isset($data['paid_amount']) ? (float) $data['paid_amount'] : null,
                            (bool) ($data['deposit_remainder_to_customer_balance'] ?? false)
                        );
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'CREATE_QUEUE_ENTRY':
                        WaitingList::create([
                            'customer_name' => $data['customer_name'],
                            'notes' => $data['notes'] ?? null,
                            'time' => $data['time'] ?? now(),
                        ]);
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'DELETE_QUEUE_ENTRY':
                        if (str_starts_with((string) $data['id'], 'local_')) {
                            WaitingList::where('customer_name', $data['customer_name'] ?? '')->latest()->first()?->delete();
                        } else {
                            WaitingList::where('id', $data['id'])->delete();
                        }
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'CREATE_RESERVATION':
                        Reservation::create([
                            'customer_name' => $data['customer_name'],
                            'start_time' => $data['start_time'],
                            'device_id' => $data['device_id'] ?? null,
                            'date' => $data['date'] ?? now()->toDateString(),
                        ]);
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    case 'DELETE_RESERVATION':
                        if (str_starts_with((string) $data['id'], 'local_')) {
                            Reservation::where('customer_name', $data['customer_name'] ?? '')->latest()->first()?->delete();
                        } else {
                            Reservation::where('id', $data['id'])->delete();
                        }
                        $results[] = ['id' => $opId, 'status' => 'success'];
                        break;

                    default:
                        $results[] = [
                            'id' => $opId,
                            'status' => 'failed',
                            'error' => 'Unknown operation type: '.$type,
                        ];
                }
            } catch (\Throwable $e) {
                Log::error('Offline Sync Error: '.$e->getMessage(), [
                    'op' => $op,
                    'trace' => $e->getTraceAsString(),
                ]);
                $results[] = [
                    'id' => $opId,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'results' => $results,
        ]);
    }
}
