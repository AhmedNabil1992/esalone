<?php

namespace App\Filament\Resources\Tenant\FinancialTransactions\Pages;

use App\Actions\Tenant\PostExpenseAction;
use App\Filament\Resources\Tenant\FinancialTransactions\FinancialTransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateFinancialTransaction extends CreateRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PostExpenseAction::class)->execute([
            'cash_box_id' => (int) $data['cash_box_id'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now()->toDateTimeString(),
            'affects_profit' => (bool) ($data['affects_profit'] ?? true),
        ], Auth::user());
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
