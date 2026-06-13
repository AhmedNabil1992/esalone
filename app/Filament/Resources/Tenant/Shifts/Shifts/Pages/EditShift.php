<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('closeShift')
                ->label('غلق الشيفت')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function (): void {
                    $record = $this->getRecord();

                    if ($record->status !== 'open') {
                        throw ValidationException::withMessages([
                            'status' => 'هذا الشيفت مغلق بالفعل.',
                        ]);
                    }

                    $record->update([
                        'status' => 'close',
                        'is_active' => false,
                        'shift_to' => now(),
                        'closed_by_user_id' => Auth::id(),
                    ]);
                }),
        ];
    }
}
