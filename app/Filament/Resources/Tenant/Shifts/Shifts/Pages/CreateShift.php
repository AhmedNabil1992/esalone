<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Shift;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $branchId = (int) ($data['branch_id']
            ?? (session('active_branch_id') && session('active_branch_id') !== 'all' ? (int) session('active_branch_id') : null)
            ?? (Auth::user()?->branches()->first()?->id ?? Branch::where('is_active', true)->orderBy('id')->value('id') ?? 1));

        $hasOpenShift = Shift::query()
            ->where('branch_id', $branchId)
            ->open()
            ->active()
            ->exists();

        if ($hasOpenShift) {
            throw ValidationException::withMessages([
                'shift_no' => 'لا يمكن فتح شيفت جديد قبل غلق الشيفت الحالي لهذا الفرع.',
            ]);
        }

        $data['branch_id'] = $branchId;
        $data['shift_no'] = Shift::nextShiftNumber($branchId);
        $data['opened_by_user_id'] = Auth::id();
        $data['shift_date'] = $data['shift_date'] ?? today()->toDateString();
        $data['shift_from'] = $data['shift_from'] ?? now()->toDateTimeString();
        $data['status'] = 'open';
        $data['is_active'] = true;

        return Shift::query()->create($data);
    }
}
