<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\SystemConfig;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveAppointmentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Appointment $appointment = null): Appointment
    {
        $validated = Validator::make($data, [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'staff_member_id' => ['required', 'integer', Rule::exists('staff_members', 'id')],
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('salon_services', 'id')],
            'start_at' => ['required', 'date'],
            'status' => ['required', Rule::in(['requested', 'scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        $actor = Auth::user();

        if (! $actor instanceof User) {
            throw new AuthorizationException('سجّل الدخول قبل إنشاء موعد.');
        }

        $branchId = (int) $validated['branch_id'];

        if (! $actor->hasAnyRole(['owner', 'super_admin', 'Admin'])
            && ! in_array($branchId, $actor->assignedBranchIds(), true)) {
            throw new AuthorizationException('لا تملك صلاحية إدارة مواعيد هذا الفرع.');
        }

        return DB::transaction(function () use ($validated, $appointment, $actor, $branchId): Appointment {
            $staffMember = StaffMember::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail((int) $validated['staff_member_id']);

            $services = SalonService::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branchId)
                ->where('is_active', true)
                ->whereIn('id', $validated['service_ids'])
                ->get();

            if ($services->count() !== count($validated['service_ids'])) {
                throw ValidationException::withMessages([
                    'service_ids' => 'اختر خدمات نشطة تابعة للفرع المحدد.',
                ]);
            }

            $lockedAppointment = $appointment
                ? Appointment::query()
                    ->withoutGlobalScope(BranchScope::class)
                    ->lockForUpdate()
                    ->findOrFail($appointment->getKey())
                : new Appointment;

            if (! $actor->hasAnyRole(['owner', 'super_admin', 'Admin'])
                && $lockedAppointment->exists
                && ! in_array((int) $lockedAppointment->branch_id, $actor->assignedBranchIds(), true)) {
                throw new AuthorizationException('لا تملك صلاحية إدارة موعد هذا الفرع.');
            }

            if ($lockedAppointment->invoice_id) {
                throw ValidationException::withMessages([
                    'appointment' => 'لا يمكن تعديل موعد بعد تحصيل قيمته.',
                ]);
            }

            $durationMinutes = (int) $services->sum('duration_minutes');
            $startAt = Carbon::parse($validated['start_at']);
            $endAt = $startAt->copy()->addMinutes($durationMinutes);
            $timeZone = (string) SystemConfig::get('sys_system_time_zone', 'Africa/Cairo');
            $scheduleStartAt = Carbon::parse($validated['start_at'], $timeZone);
            $scheduleEndAt = $scheduleStartAt->copy()->addMinutes($durationMinutes);

            if (in_array($validated['status'], Appointment::RESERVED_STATUSES, true)
                && ! $staffMember->isAvailableDuring($scheduleStartAt, $scheduleEndAt)) {
                throw ValidationException::withMessages([
                    'start_at' => 'الموظف لا يعمل خلال الفترة المحددة في جدول دوامه.',
                ]);
            }

            if (Appointment::hasConflict(
                $branchId,
                $staffMember->getKey(),
                $startAt->toDateTimeString(),
                $endAt->toDateTimeString(),
                $lockedAppointment->exists ? $lockedAppointment->getKey() : null,
            )) {
                throw ValidationException::withMessages([
                    'start_at' => 'هذا الموظف لديه موعد آخر يتعارض مع الفترة الزمنية المحددة.',
                ]);
            }

            $lockedAppointment->fill([
                'branch_id' => $branchId,
                'staff_member_id' => $staffMember->getKey(),
                'customer_id' => $validated['customer_id'] ?? null,
                'created_by_user_id' => $lockedAppointment->exists
                    ? $lockedAppointment->created_by_user_id
                    : $actor->getKey(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration_minutes' => $durationMinutes,
                'total_price' => $services->sum('price'),
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);
            $lockedAppointment->save();
            $lockedAppointment->items()->delete();

            foreach ($services as $service) {
                $lockedAppointment->items()->create([
                    'salon_service_id' => $service->getKey(),
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'unit_price' => $service->price,
                ]);
            }

            return $lockedAppointment->load(['customer', 'staffMember', 'items']);
        });
    }
}
