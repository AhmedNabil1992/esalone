<?php

namespace App\Actions\Tenant;

use App\Models\Tenant\Appointment;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\MarketHistory;
use App\Models\Tenant\Shift;
use App\Models\Tenant\User;
use App\Scopes\BranchScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class CheckoutAppointmentAction
{
    public function __construct(
        private readonly CreateDirectSaleAction $createDirectSaleAction,
        private readonly RecordAppointmentCommissionAction $recordAppointmentCommissionAction,
    ) {}

    public function execute(Appointment $appointment): Invoice
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            throw new AuthorizationException('سجّل الدخول قبل تحصيل قيمة الموعد.');
        }

        return DB::transaction(function () use ($appointment, $actor): Invoice {
            $lockedAppointment = Appointment::query()
                ->withoutGlobalScope(BranchScope::class)
                ->lockForUpdate()
                ->findOrFail($appointment->getKey());

            if (! $actor->hasAnyRole(['owner', 'super_admin', 'Admin'])
                && ! in_array((int) $lockedAppointment->branch_id, $actor->assignedBranchIds(), true)) {
                throw new AuthorizationException('لا تملك صلاحية تحصيل موعد هذا الفرع.');
            }

            if ($lockedAppointment->invoice_id) {
                return $lockedAppointment->invoice()->firstOrFail();
            }

            if ($lockedAppointment->status !== 'completed') {
                throw ValidationException::withMessages([
                    'appointment' => 'أكمل الخدمة قبل تحصيل قيمتها.',
                ]);
            }

            if (! Shift::currentOpenShift((int) $lockedAppointment->branch_id)) {
                throw ValidationException::withMessages([
                    'appointment' => 'افتح وردية في فرع الموعد قبل التحصيل.',
                ]);
            }

            $appointmentItems = $lockedAppointment->items()->with('salonService.catalogItem')->get();

            if ($appointmentItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'appointment' => 'لا توجد خدمات مسجلة على هذا الموعد.',
                ]);
            }

            $items = [];
            $catalogItemNames = [];

            foreach ($appointmentItems as $appointmentItem) {
                $service = $appointmentItem->salonService;

                if (! $service || ! $service->catalogItem) {
                    throw ValidationException::withMessages([
                        'appointment' => 'تعذر تجهيز أحد خدمات الموعد للبيع. راجع إعدادات قائمة الخدمات.',
                    ]);
                }

                $unitPrice = (float) $appointmentItem->unit_price;
                $catalogItemId = (int) $service->catalogItem->getKey();

                $items[] = [
                    'catalog_item_id' => $catalogItemId,
                    'quantity' => 1,
                    'unit_price' => $unitPrice,
                    'total_amount' => $unitPrice,
                ];
                $catalogItemNames[$catalogItemId] = $appointmentItem->service_name;
            }

            $activeBranchId = session('active_branch_id');
            session(['active_branch_id' => (int) $lockedAppointment->branch_id]);

            try {
                $sale = $this->createDirectSaleAction->execute(
                    items: $items,
                    customerId: $lockedAppointment->customer_id ? (int) $lockedAppointment->customer_id : null,
                    actor: $actor,
                );
            } finally {
                if ($activeBranchId === null) {
                    session()->forget('active_branch_id');
                } else {
                    session(['active_branch_id' => $activeBranchId]);
                }
            }

            $invoice = $sale['invoice'] ?? null;

            if (! $invoice instanceof Invoice) {
                throw new LogicException('Appointment checkout did not return an invoice.');
            }

            $lockedAppointment->forceFill(['invoice_id' => $invoice->getKey()])->save();
            $this->recordAppointmentCommissionAction->execute($lockedAppointment, $invoice);

            foreach ($catalogItemNames as $catalogItemId => $serviceName) {
                MarketHistory::query()
                    ->where('invoice_no', $invoice->invoice_no)
                    ->where('catalog_item_id', $catalogItemId)
                    ->update(['subject_name' => $serviceName]);
            }

            return $invoice;
        });
    }
}
