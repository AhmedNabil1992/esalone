<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Appointment;
use App\Scopes\BranchScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelPublicAppointmentAction
{
    public function execute(string $token): Appointment
    {
        return DB::transaction(function () use ($token): Appointment {
            $appointment = Appointment::query()
                ->withoutGlobalScope(BranchScope::class)
                ->lockForUpdate()
                ->where('public_booking_token_hash', hash('sha256', $token))
                ->firstOrFail();

            if (! in_array($appointment->status, ['requested', 'confirmed'], true)
                || $appointment->invoice_id !== null
                || ! $appointment->start_at->isFuture()) {
                throw ValidationException::withMessages([
                    'appointment' => 'لا يمكن إلغاء هذا الموعد من الرابط.',
                ]);
            }

            $appointment->update(['status' => 'cancelled']);

            return $appointment;
        });
    }
}
