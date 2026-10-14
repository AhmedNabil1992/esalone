<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\StaffCommission;
use App\Models\Tenant\StaffMember;
use App\Scopes\BranchScope;

class RecordAppointmentCommissionAction
{
    public function execute(Appointment $appointment, Invoice $invoice): ?StaffCommission
    {
        if ((int) $invoice->branch_id !== (int) $appointment->branch_id
            || (float) $invoice->remain_amount > 0
            || (float) $invoice->paid_amount < (float) $invoice->final_amount) {
            return null;
        }

        $staffMember = StaffMember::query()
            ->withoutGlobalScope(BranchScope::class)
            ->where('branch_id', $appointment->branch_id)
            ->findOrFail($appointment->staff_member_id);

        $rate = (float) $staffMember->commission_rate;
        $commissionBase = round(max(0, (float) $invoice->subtotal - (float) $invoice->discount_amount), 2);

        if ($rate <= 0 || $commissionBase <= 0) {
            return null;
        }

        return StaffCommission::query()
            ->withoutGlobalScope(BranchScope::class)
            ->firstOrCreate(
                ['appointment_id' => $appointment->getKey()],
                [
                    'branch_id' => $appointment->branch_id,
                    'staff_member_id' => $staffMember->getKey(),
                    'invoice_id' => $invoice->getKey(),
                    'commission_rate' => $rate,
                    'commission_base' => $commissionBase,
                    'amount' => round($commissionBase * $rate / 100, 2),
                ],
            );
    }
}
