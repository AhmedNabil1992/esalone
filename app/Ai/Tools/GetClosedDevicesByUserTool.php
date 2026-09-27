<?php

namespace App\Ai\Tools;

use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Support\ArabicSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetClosedDevicesByUserTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'جلب الأجهزة أو الترابيزات التي أغلق المستخدم فواتير جلساتها في وردية محددة، مع التحقق المستقل من وجود المستخدم والوردية.';
    }

    public function handle(Request $request): Stringable|string
    {
        $userName = trim((string) ($request['user_name'] ?? ''));
        $shiftNo = $request['shift_no'] ?? null;

        if ($userName === '') {
            return json_encode(['status' => 'user_name_required'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $users = User::query()->get(['id', 'name']);
        $matchedUsers = $users
            ->filter(fn (User $user): bool => ArabicSearch::matches($user->name, $userName))
            ->values();

        if ($matchedUsers->isEmpty()) {
            return json_encode([
                'status' => 'user_not_matched',
                'searched_for' => $userName,
                'suggestions' => ArabicSearch::suggestions($users->pluck('name'), $userName),
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        }

        $shift = null;
        if ($shiftNo) {
            $shift = Shift::query()->where('shift_no', $shiftNo)->first();
            if (! $shift) {
                return json_encode([
                    'status' => 'shift_not_found',
                    'shift_no' => (int) $shiftNo,
                    'matched_users' => $matchedUsers->pluck('name')->all(),
                ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            }
        }

        $query = DB::table('invoices')
            ->join('devices', 'invoices.device_id', '=', 'devices.id')
            ->join('users', 'invoices.created_by_user_id', '=', 'users.id')
            ->leftJoin('shifts', 'invoices.shift_id', '=', 'shifts.id')
            ->where('invoices.status', 'posted')
            ->where('invoices.invoice_type', 'device_sale')
            ->whereIn('invoices.created_by_user_id', $matchedUsers->pluck('id'))
            ->when($shift, fn ($builder, Shift $selectedShift) => $builder->where('invoices.shift_id', $selectedShift->id))
            ->when($this->activeBranchId(), fn ($builder, int $branchId) => $builder->where('invoices.branch_id', $branchId))
            ->select(
                'users.name AS user_name',
                'devices.id AS device_id',
                'devices.name AS device_name',
                'shifts.shift_no',
                DB::raw('COUNT(invoices.id) AS invoices_count'),
                DB::raw('SUM(invoices.final_amount) AS total_amount'),
                DB::raw('MAX(invoices.issued_at) AS last_closed_at')
            )
            ->groupBy('users.id', 'users.name', 'devices.id', 'devices.name', 'shifts.shift_no')
            ->orderByDesc('last_closed_at')
            ->limit(30)
            ->get();

        return json_encode([
            'status' => $query->isEmpty() ? 'user_found_but_no_closed_devices_in_scope' : 'ok',
            'searched_for' => $userName,
            'matched_users' => $matchedUsers->pluck('name')->all(),
            'shift_no' => $shiftNo ? (int) $shiftNo : null,
            'devices' => $query,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'user_name' => $schema->string()->description('اسم المستخدم الذي أغلق جلسات الأجهزة.')->required(),
            'shift_no' => $schema->integer()->description('رقم الوردية المطلوبة.'),
        ];
    }

    private function activeBranchId(): ?int
    {
        $branchId = session('active_branch_id');

        return is_numeric($branchId) ? (int) $branchId : null;
    }
}
