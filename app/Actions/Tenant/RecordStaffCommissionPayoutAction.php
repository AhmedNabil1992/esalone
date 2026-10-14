<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\CashBox;
use App\Models\Tenant\Shift;
use App\Models\Tenant\StaffCommission;
use App\Models\Tenant\StaffCommissionPayout;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordStaffCommissionPayoutAction
{
    public function __construct(private readonly PostExpenseAction $postExpenseAction) {}

    public function execute(StaffCommission $commission, int $cashBoxId, float $amount): StaffCommissionPayout
    {
        $actor = Auth::user();

        if (! $actor instanceof User || ! $actor->can('Update:StaffMember')) {
            throw new AuthorizationException('لا تملك صلاحية تسجيل صرف عمولات الموظفين.');
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'أدخل مبلغًا أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ($actor, $commission, $cashBoxId, $amount): StaffCommissionPayout {
            $lockedCommission = StaffCommission::query()
                ->withoutGlobalScope(BranchScope::class)
                ->lockForUpdate()
                ->findOrFail($commission->getKey());

            if (! $actor->hasAnyRole(['owner', 'super_admin', 'Admin'])
                && ! in_array((int) $lockedCommission->branch_id, $actor->assignedBranchIds(), true)) {
                throw new AuthorizationException('لا تملك صلاحية صرف عمولة هذا الفرع.');
            }

            $paidAmount = (float) $lockedCommission->payouts()->sum('amount');
            $remainingAmount = round(max(0, (float) $lockedCommission->amount - $paidAmount), 2);
            $amount = round($amount, 2);

            if ($amount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'لا يمكن صرف مبلغ أكبر من العمولة المتبقية.',
                ]);
            }

            $cashBox = CashBox::query()
                ->withoutGlobalScope(BranchScope::class)
                ->whereKey($cashBoxId)
                ->where('branch_id', $lockedCommission->branch_id)
                ->where('is_active', true)
                ->first();

            if (! $cashBox) {
                throw ValidationException::withMessages([
                    'cash_box_id' => 'اختر خزينة نشطة تابعة لفرع الموظف.',
                ]);
            }

            $shift = Shift::query()
                ->withoutGlobalScope(BranchScope::class)
                ->open()
                ->active()
                ->where('branch_id', $lockedCommission->branch_id)
                ->orderByDesc('id')
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'cash_box_id' => 'افتح وردية في فرع الموظف قبل تسجيل صرف العمولة.',
                ]);
            }

            $expense = $this->postExpenseAction->execute([
                'cash_box_id' => (int) $cashBox->getKey(),
                'amount' => $amount,
                'description' => sprintf(
                    'صرف عمولة %s عن الموعد رقم %d',
                    StaffMember::query()
                        ->withoutGlobalScope(BranchScope::class)
                        ->whereKey($lockedCommission->staff_member_id)
                        ->value('name'),
                    $lockedCommission->appointment_id,
                ),
                'affects_profit' => true,
                'shift_id' => (int) $shift->getKey(),
            ], $actor);

            $payout = StaffCommissionPayout::query()->create([
                'branch_id' => $lockedCommission->branch_id,
                'staff_commission_id' => $lockedCommission->getKey(),
                'expense_history_id' => $expense->getKey(),
                'created_by_user_id' => $actor->getKey(),
                'amount' => $amount,
            ]);

            $expense->forceFill([
                'reference_type' => StaffCommissionPayout::class,
                'reference_id' => $payout->getKey(),
            ])->save();

            return $payout->load(['commission.staffMember', 'expenseHistory', 'createdBy']);
        });
    }
}
