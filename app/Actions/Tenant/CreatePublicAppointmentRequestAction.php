<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\SystemConfig;
use App\Scopes\BranchScope;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CreatePublicAppointmentRequestAction
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{appointment: Appointment, token: string}
     */
    public function execute(array $data): array
    {
        $validated = Validator::make($data, [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'staff_member_id' => ['required', 'integer', Rule::exists('staff_members', 'id')],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', 'distinct', Rule::exists('salon_services', 'id')],
            'start_at' => ['required', 'date'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $timeZone = (string) SystemConfig::get('sys_system_time_zone', 'Africa/Cairo');
        $startAt = Carbon::parse($validated['start_at'], $timeZone);

        if ($startAt->isPast()) {
            throw ValidationException::withMessages([
                'start_at' => 'اختر تاريخًا ووقتًا في المستقبل.',
            ]);
        }

        $startAt = $startAt->utc();

        return DB::transaction(function () use ($validated, $startAt, $timeZone): array {
            $branchId = (int) $validated['branch_id'];
            $branch = Branch::query()
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail($branchId);

            $staffMember = StaffMember::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->getKey())
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail((int) $validated['staff_member_id']);

            $services = SalonService::query()
                ->withoutGlobalScope(BranchScope::class)
                ->where('branch_id', $branch->getKey())
                ->where('is_active', true)
                ->whereIn('id', $validated['service_ids'])
                ->get();

            if ($services->count() !== count($validated['service_ids'])) {
                throw ValidationException::withMessages([
                    'service_ids' => 'اختر خدمات متاحة تابعة للفرع المحدد.',
                ]);
            }

            $durationMinutes = (int) $services->sum('duration_minutes');
            $endAt = $startAt->copy()->addMinutes($durationMinutes);

            if (! $staffMember->isAvailableDuring(
                $startAt->copy()->setTimezone($timeZone),
                $endAt->copy()->setTimezone($timeZone),
            )) {
                throw ValidationException::withMessages([
                    'start_at' => 'الموظف لا يعمل خلال الفترة المحددة في جدول دوامه.',
                ]);
            }

            if (Appointment::hasConflict(
                $branchId,
                (int) $staffMember->getKey(),
                $startAt->toDateTimeString(),
                $endAt->toDateTimeString(),
            )) {
                throw ValidationException::withMessages([
                    'start_at' => 'الفترة المطلوبة غير متاحة لهذا الموظف. اختر وقتًا آخر.',
                ]);
            }

            $token = bin2hex(random_bytes(32));
            $appointment = Appointment::query()->create([
                'branch_id' => $branchId,
                'staff_member_id' => $staffMember->getKey(),
                'contact_name' => $validated['contact_name'],
                'contact_phone' => $validated['contact_phone'],
                'public_booking_token_hash' => hash('sha256', $token),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'duration_minutes' => $durationMinutes,
                'total_price' => $services->sum('price'),
                'status' => 'requested',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($services as $service) {
                $appointment->items()->create([
                    'salon_service_id' => $service->getKey(),
                    'service_name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'unit_price' => $service->price,
                ]);
            }

            return [
                'appointment' => $appointment->load(['branch', 'staffMember', 'items']),
                'token' => $token,
            ];
        });
    }
}
