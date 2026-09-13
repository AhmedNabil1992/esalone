<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class OpenShiftAction
{
    /** @param array<string, mixed> $data */
    public function execute(User $actor, array $data = []): Shift
    {
        abort_unless(tenancy()->initialized, 403);
        Gate::forUser($actor)->authorize('create', Shift::class);

        $isOwner = $actor->hasAnyRole(['owner', 'Admin', config('filament-shield.super_admin.name', 'super_admin')]);
        $activeBranch = session('active_branch_id');
        $branchId = (int) ($data['branch_id']
            ?? ($activeBranch && $activeBranch !== 'all' ? $activeBranch : null)
            ?? $actor->branches()->orderBy('branches.id')->value('branches.id')
            ?? ($isOwner ? Branch::on('tenant')->where('is_active', true)->orderBy('id')->value('id') : null));

        abort_unless($isOwner || $actor->branches()->whereKey($branchId)->exists(), 403);

        return DB::connection('tenant')->transaction(function () use ($actor, $data, $branchId): Shift {
            $branch = Branch::on('tenant')->whereKey($branchId)->lockForUpdate()->firstOrFail();
            abort_unless($branch->is_active, 403);

            $shifts = Shift::on('tenant')->withoutGlobalScope(BranchScope::class)->where('branch_id', $branchId);
            if ((clone $shifts)->where('status', 'open')->exists()) {
                throw ValidationException::withMessages([
                    'shift_no' => 'لا يمكن فتح شيفت جديد قبل إغلاق الشيفت الحالي لهذا الفرع.',
                ]);
            }

            $lastClosedShift = (clone $shifts)->where('status', 'close')->orderByDesc('shift_no')->first();

            return Shift::on('tenant')->create([
                'branch_id' => $branchId,
                'shift_no' => (int) ((clone $shifts)->max('shift_no') ?? 0) + 1,
                'opened_by_user_id' => $actor->id,
                'shift_date' => today()->toDateString(),
                'shift_from' => now(),
                'start_amount' => $data['start_amount'] ?? $lastClosedShift?->actual_amount ?? 0,
                'actual_amount' => 0,
                'status' => 'open',
                'is_active' => true,
                'notify' => false,
                'notes' => $data['notes'] ?? null,
            ]);
        }, 3);
    }
}
