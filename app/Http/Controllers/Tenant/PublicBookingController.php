<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\CancelPublicAppointmentAction;
use App\Actions\Tenant\CreatePublicAppointmentRequestAction;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Appointment;
use App\Models\Tenant\Branch;
use App\Models\Tenant\SalonService;
use App\Models\Tenant\StaffMember;
use App\Models\Tenant\SystemConfig;
use App\Scopes\BranchScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function create(Request $request): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $branchIds = $branches->modelKeys();
        $services = SalonService::query()
            ->withoutGlobalScope(BranchScope::class)
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'duration_minutes', 'price']);
        $staffMembers = StaffMember::query()
            ->withoutGlobalScope(BranchScope::class)
            ->whereIn('branch_id', $branchIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'job_title']);
        $requestedService = $services->firstWhere('id', $request->integer('service_id'));
        $requestedBranchId = $request->integer('branch_id') ?: $requestedService?->branch_id;
        $selectedBranchId = $branches->firstWhere('id', $requestedBranchId)?->id
            ?? $branches->first()?->id;
        $selectedServiceId = $requestedService?->branch_id === $selectedBranchId
            ? $requestedService->id
            : null;

        return view('tenant.bookings.create', [
            'branches' => $branches,
            'services' => $services,
            'staffMembers' => $staffMembers,
            'selectedBranchId' => $selectedBranchId,
            'selectedServiceId' => $selectedServiceId,
            'timeZone' => (string) SystemConfig::get('sys_system_time_zone', 'Africa/Cairo'),
            'shopName' => (string) SystemConfig::get('sys_shop_name', 'الصالون'),
        ]);
    }

    public function store(Request $request, CreatePublicAppointmentRequestAction $action): RedirectResponse
    {
        $booking = $action->execute($request->only([
            'branch_id',
            'staff_member_id',
            'service_ids',
            'start_at',
            'contact_name',
            'contact_phone',
            'notes',
        ]));

        return redirect()->route('tenant.bookings.confirmation', [
            'token' => $booking['token'],
        ]);
    }

    public function confirmation(string $token): View
    {
        $appointment = Appointment::query()
            ->withoutGlobalScope(BranchScope::class)
            ->with(['branch', 'staffMember', 'items'])
            ->where('public_booking_token_hash', hash('sha256', $token))
            ->firstOrFail();

        return view('tenant.bookings.confirmation', [
            'appointment' => $appointment,
            'timeZone' => (string) SystemConfig::get('sys_system_time_zone', 'Africa/Cairo'),
            'shopName' => (string) SystemConfig::get('sys_shop_name', 'الصالون'),
            'token' => $token,
        ]);
    }

    public function cancel(string $token, CancelPublicAppointmentAction $action): RedirectResponse
    {
        $action->execute($token);

        return redirect()->route('tenant.bookings.confirmation', ['token' => $token]);
    }
}
