<?php

namespace App\Filament\Resources\Tenant\ExpenseHistories\Pages;

use App\Actions\Tenant\PostExpenseAction;
use App\Filament\Resources\Tenant\ExpenseHistories\ExpenseHistoryResource;
use App\Models\Tenant\Shift;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateExpenseHistory extends CreateRecord
{
    protected static string $resource = ExpenseHistoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PostExpenseAction::class)->execute([
            'cash_box_id' => (int) $data['cash_box_id'],
            'expenses_id' => (int) $data['expenses_id'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now()->toDateTimeString(),
            'affects_profit' => (bool) ($data['affects_profit'] ?? true),
            'shift_id' => Shift::currentOpenShiftId(),
        ], Auth::user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function mount(): void
    {
        parent::mount();

        $this->data['shift_id'] = Shift::currentOpenShiftId();
    }
}
