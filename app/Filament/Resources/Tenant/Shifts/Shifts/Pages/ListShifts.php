<?php

namespace App\Filament\Resources\Tenant\Shifts\Shifts\Pages;

use App\Filament\Resources\Tenant\Shifts\Shifts\ShiftResource;
use App\Models\Tenant\Shift;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('فتح وردية جديدة')
                ->url(fn (): string => route('tenant.shifts.quick-open', ['redirect' => ShiftResource::getUrl('index')]))
                ->visible(fn (): bool => ! Shift::query()->where('status', 'open')->exists()),
            Action::make('closeActiveShift')
                ->label('تقفيل الوردية الحالية')
                ->color('danger')
                ->icon('heroicon-o-lock-closed')
                ->url(function () {
                    $activeShift = Shift::currentOpenShift();

                    return $activeShift ? ShiftResource::getUrl('close', ['record' => $activeShift]) : null;
                })
                ->visible(fn () => Shift::query()->open()->active()->exists()),
        ];
    }
}
