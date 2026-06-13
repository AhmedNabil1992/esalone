<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
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
        $hasOpenShift = Shift::query()
            ->open()
            ->active()
            ->exists();

        if ($hasOpenShift) {
            throw ValidationException::withMessages([
                'shift_no' => 'لا يمكن فتح شيفت جديد قبل غلق الشيفت الحالي.',
            ]);
        }

        $data['shift_no'] = Shift::nextShiftNumber();
        $data['opened_by_user_id'] = Auth::id();
        $data['shift_date'] = $data['shift_date'] ?? today()->toDateString();
        $data['shift_from'] = $data['shift_from'] ?? now()->toDateTimeString();
        $data['status'] = 'open';
        $data['is_active'] = true;

        return Shift::query()->create($data);
    }
}
